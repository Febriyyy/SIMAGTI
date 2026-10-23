<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\TimeEntry;
use App\Support\BoardData;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pemantauan kegiatan mahasiswa oleh Company Supervisor, dosen pembimbing, dan kaprodi (hanya baca + komentar).
 * Mahasiswa dikelompokkan per kloter: perusahaan, divisi, dan periode yang sama.
 */
class MonitorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = in_array($request->query('status'), ['berjalan', 'selesai'], true) ? $request->query('status') : 'berjalan';
        $term = mb_substr(trim((string) $request->query('cari')), 0, 60);

        $internships = Internship::visibleTo($user)
            ->with(['student.studentProfile.studyProgram', 'company', 'division', 'supervisor', 'lecturer'])
            ->withProgress()
            ->withSum('timeEntries as logged_hours', 'hours')
            ->where('status', $status)
            ->when($term !== '', fn ($q) => $q->whereHas('student', fn ($s) => $s
                ->where('name', 'like', '%'.$term.'%')->orWhere('nim', 'like', '%'.$term.'%')))
            ->orderBy('start_date')
            ->get();

        $lastActivity = TimeEntry::whereIn('internship_id', $internships->pluck('id'))
            ->groupBy('internship_id')
            ->select('internship_id', DB::raw('MAX(work_date) as last_date'))
            ->pluck('last_date', 'internship_id');

        $cohorts = $internships
            ->groupBy(fn (Internship $i) => $i->isSeminar() ? 'seminar|'.$i->start_date->toDateString().'|'.$i->end_date->toDateString() : $i->cohortKey())
            ->map(fn ($items) => ['first' => $items->first(), 'items' => $items])
            ->sortBy(fn ($c) => $c['first']->placeName().$c['first']->start_date->toDateString())
            ->values()
            ->all();

        // Nomor kloter per perusahaan + divisi supaya dua kelompok di divisi yang sama mudah dibedakan.
        $counter = [];
        foreach ($cohorts as $i => $cohort) {
            $base = $cohort['first']->company_id.'|'.$cohort['first']->division_id;
            $counter[$base] = ($counter[$base] ?? 0) + 1;
            $cohorts[$i]['number'] = $counter[$base];
        }
        foreach ($cohorts as $i => $cohort) {
            $base = $cohort['first']->company_id.'|'.$cohort['first']->division_id;
            $cohorts[$i]['many'] = $counter[$base] > 1;
        }

        $cohorts = collect($cohorts);

        return view('pantau.index', compact('cohorts', 'status', 'term', 'lastActivity', 'user'));
    }

    public function show(Request $request, Internship $internship): View
    {
        Gate::authorize('view', $internship);

        $internship->load(['student.studentProfile.studyProgram', 'company', 'division', 'supervisor', 'lecturer', 'syllabusItems.tasks']);
        $tab = in_array($request->query('tab'), ['kegiatan', 'timesheet', 'silabus'], true) ? $request->query('tab') : 'kegiatan';

        $data = [
            'internship' => $internship,
            'tab' => $tab,
            'canReviewSyllabus' => $request->user()->can('reviewSyllabus', $internship),
            'canCorrect' => $request->user()->can('correctPosition', $internship),
            'stats' => [
                'tasks' => $internship->tasks()->count(),
                'done' => $internship->tasks()->where('status', 'completed')->count(),
                'hours' => (float) $internship->timeEntries()->sum('hours'),
                'days' => $internship->timeEntries()->distinct('work_date')->count('work_date'),
            ],
        ];

        if ($tab === 'kegiatan') {
            $data += BoardData::build($internship, $request);
        } elseif ($tab === 'timesheet') {
            $offset = max(-60, min(0, $request->integer('p')));
            $all = $request->query('periode') === 'semua';
            $period = Period::for($internship, null, $offset);
            $entries = $internship->timeEntries()->with(['task.project', 'photo'])
                ->when(! $all, fn ($q) => $q->whereBetween('work_date', [$period->start->toDateString(), $period->end->toDateTimeString()]))
                ->orderByDesc('work_date')->orderByDesc('id')->get();
            $data += [
                'period' => $period,
                'offset' => $offset,
                'all' => $all,
                'entries' => $entries,
                'days' => $entries->groupBy(fn ($e) => $e->work_date->toDateString()),
            ];
        }

        return view('pantau.show', $data);
    }

    /** Company Supervisor mengoreksi posisi magang yang diisi mahasiswa. */
    public function position(Request $request, Internship $internship): RedirectResponse
    {
        Gate::authorize('correctPosition', $internship);
        $data = $request->validate(['position' => ['required', 'string', 'max:100']], [], ['position' => 'posisi']);

        $old = $internship->position;
        $internship->update($data);
        ActivityLog::record('kp.posisi', "Mengoreksi posisi {$internship->student->name}: ".($old ?? '–')." → {$data['position']}", $internship);

        return back()->with('status', 'Posisi magang diperbarui.');
    }
}
