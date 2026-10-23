<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildMonitoringDashboard;
use App\Models\Company;
use App\Models\Internship;
use App\Models\InternshipStaff;
use App\Models\Invitation;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WeeklyMeeting;
use App\Models\WeeklyTask;
use App\Support\KpProgress;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Jumlah minggu di grafik jam kegiatan mahasiswa. */
    private const HOUR_WEEKS = 8;

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            'mahasiswa' => $this->mahasiswa($user),
            'supervisor' => $this->supervisor($user),
            'tu' => $this->tu($user),
            'biro' => $this->biro($user),
            default => $this->pantau($user), // dospem, kaprodi
        };
    }

    private function mahasiswa(User $user): View
    {
        $internship = Internship::with(['company', 'division', 'supervisor', 'lecturer', 'assessment'])
            ->withProgress()
            ->withSum('timeEntries as logged_hours', 'hours')
            ->where('student_id', $user->id)
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 WHEN 'diajukan' THEN 1 WHEN 'selesai' THEN 2 ELSE 3 END")
            ->latest('start_date')
            ->first();

        $data = ['user' => $user, 'internship' => $internship, 'tasks' => collect(), 'meeting' => null,
            'hours' => null, 'period' => null, 'next' => null, 'periodHours' => 0.0];

        if (! $internship) {
            return view('dashboard.mahasiswa', $data);
        }

        $data['next'] = KpProgress::current($internship);

        if (in_array($internship->status, ['berjalan', 'selesai'], true)) {
            $period = Period::for($internship);

            // Task yang perlu dikerjakan: belum selesai, jatuh tempo sampai akhir periode ini (atau tanpa tenggat tapi sedang dikerjakan)
            $data['tasks'] = $internship->tasks()
                ->with(['project', 'column'])
                ->withCount(['evidences', 'comments'])
                ->where('status', '!=', 'completed')
                ->where(fn ($q) => $q->where('due_date', '<=', $period->end->toDateTimeString())
                    ->orWhere(fn ($q) => $q->whereNull('due_date')->where('status', 'in_progress')))
                ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_date')
                ->limit(6)
                ->get();

            $data['period'] = $period;
            $data['periodHours'] = (float) $internship->timeEntries()
                ->whereBetween('work_date', [$period->start->toDateString(), $period->end->toDateTimeString()])
                ->sum('hours');
            $data['meeting'] = $internship->currentMeeting();
            $data['hours'] = $this->weeklyHours(collect([$internship->id]));
        }

        return view('dashboard.mahasiswa', $data);
    }

    /** Company Supervisor: memantau mahasiswa magang, menyetujui silabus, mengatur meeting. */
    private function supervisor(User $user): View
    {
        $internships = Internship::with(['student.studentProfile.studyProgram', 'company', 'division', 'assessment'])
            ->withProgress()
            ->withSum('timeEntries as logged_hours', 'hours')
            ->supervisedBy($user)
            ->where('status', 'berjalan')
            ->orderBy('start_date')
            ->get();

        $ids = $internships->pluck('id');
        $weekStart = Carbon::today()->startOfWeek();

        $weekHours = TimeEntry::whereIn('internship_id', $ids)
            ->where('work_date', '>=', $weekStart->toDateString())
            ->selectRaw('internship_id, SUM(hours) as total')
            ->groupBy('internship_id')
            ->pluck('total', 'internship_id');

        $syllabus = $internships->where('syllabus_status', 'submitted')->values();

        $stats = [
            'students' => $internships->count(),
            'syllabus' => $syllabus->count(),
            'week_hours' => (float) $weekHours->sum(),
            'to_assess' => Internship::supervisedBy($user)
                ->whereIn('status', ['berjalan', 'selesai'])
                ->whereDoesntHave('assessment', fn ($q) => $q->whereIn('status', ['submitted', 'validated']))
                ->count(),
        ];

        $statusCounts = WeeklyTask::whereIn('internship_id', $ids)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recent = TimeEntry::with(['internship.student', 'task'])
            ->whereIn('internship_id', $ids)
            ->latest('work_date')
            ->latest('id')
            ->limit(6)
            ->get();

        [$groups, $withoutMeeting] = $this->meetingGroups($internships);

        return view('dashboard.supervisor', compact('user', 'internships', 'stats', 'syllabus', 'statusCounts', 'recent', 'groups', 'withoutMeeting', 'weekHours'));
    }

    /** TU Fakultas: antrean aktivasi Company Supervisor. */
    private function tu(User $user): View
    {
        $pending = Internship::awaitingSupervisor()->with(['student', 'company'])->orderBy('start_date')->get();

        $invited = Invitation::whereIn('email', $pending->pluck('supervisor_email')->unique())
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->pluck('email')
            ->unique();

        $groups = $pending
            ->groupBy(fn (Internship $i) => $i->company_id.'|'.$i->supervisor_email)
            ->map(fn (Collection $items) => [
                'first' => $items->first(),
                'count' => $items->count(),
                'invited' => $invited->contains($items->first()->supervisor_email),
            ])
            ->values();

        $stats = [
            'pending' => $groups->count(),
            'students' => $pending->count(),
            'invited' => $groups->where('invited', true)->count(),
            'active' => InternshipStaff::where('role', InternshipStaff::ROLE_SUPERVISOR)->distinct('user_id')->count('user_id'),
            'running' => Internship::where('program', 'magang')->where('status', 'berjalan')->count(),
        ];

        $recent = InternshipStaff::with(['user', 'internship.student', 'internship.company'])
            ->where('role', InternshipStaff::ROLE_SUPERVISOR)
            ->latest('id')
            ->limit(5)
            ->get();

        return view('dashboard.tu', compact('user', 'groups', 'stats', 'recent'));
    }

    /** Dosen pembimbing dan kaprodi: daftar pemantauan. */
    private function pantau(User $user): View
    {
        $subtitle = [
            'dospem' => 'Mahasiswa bimbingan Anda.',
            'kaprodi' => 'Seluruh mahasiswa kerja praktik.',
        ][$user->role] ?? '';

        return view('dashboard.pantau', ['user' => $user, 'subtitle' => $subtitle] + app(BuildMonitoringDashboard::class)->handle($user));
    }

    private function biro(User $user): View
    {
        $companies = Company::withCount([
                'internships',
                'internships as active_internships_count' => fn ($q) => $q->where('status', 'berjalan'),
            ])
            ->withAvg(['assessments as average_score' => fn ($q) => $q->where('assessments.status', 'validated')], 'final_score')
            ->orderByDesc('active_internships_count')
            ->orderBy('name')
            ->get();

        $stats = [
            'partners' => $companies->whereIn('status', ['mitra', 'mandiri_terverifikasi'])->count(),
            'waiting' => $companies->where('status', 'mandiri_menunggu')->count(),
            'students' => Internship::where('program', 'magang')->where('status', 'berjalan')->count(),
        ];

        $waiting = $companies->where('status', 'mandiri_menunggu')->values();

        return view('dashboard.biro', compact('user', 'companies', 'stats', 'waiting'));
    }

    /**
     * Meeting minggu ini, mahasiswa dengan jadwal sama digabung.
     *
     * @return array{0: Collection, 1: list<User>}
     */
    private function meetingGroups(Collection $internships): array
    {
        $meetings = WeeklyMeeting::whereIn('internship_id', $internships->pluck('id'))->get()
            ->keyBy(fn (WeeklyMeeting $m) => $m->internship_id.'-'.$m->week_number);

        $groups = [];
        $without = [];
        foreach ($internships as $internship) {
            $m = $meetings->get($internship->id.'-'.$internship->currentWeek());
            if ($m) {
                $key = $m->day.'|'.$m->time.'|'.$m->url;
                $groups[$key] ??= ['meeting' => $m, 'students' => []];
                $groups[$key]['students'][] = $internship->student;
            } else {
                $without[] = $internship->student;
            }
        }

        $groups = collect($groups)
            ->sortBy(fn (array $g) => $g['meeting']->dayOrder() * 10000 + (int) str_replace(':', '', $g['meeting']->time))
            ->values();

        return [$groups, $without];
    }

    /**
     * Jam kegiatan per minggu kalender, beberapa minggu terakhir.
     *
     * @return array{labels: list<string>, tips: list<string>, values: list<float>}
     */
    private function weeklyHours(Collection $ids): array
    {
        $first = Carbon::today()->startOfWeek()->subWeeks(self::HOUR_WEEKS - 1);
        $rows = TimeEntry::whereIn('internship_id', $ids)
            ->where('work_date', '>=', $first->toDateString())
            ->get(['work_date', 'hours']);

        $values = array_fill(0, self::HOUR_WEEKS, 0.0);
        foreach ($rows as $row) {
            $i = (int) floor($first->diffInDays($row->work_date->copy()->startOfDay()) / 7);
            if ($i >= 0 && $i < self::HOUR_WEEKS) {
                $values[$i] += (float) $row->hours;
            }
        }

        $labels = [];
        $tips = [];
        for ($i = 0; $i < self::HOUR_WEEKS; $i++) {
            $start = $first->copy()->addWeeks($i)->locale('id');
            $labels[] = $start->translatedFormat('j M');
            $tips[] = $start->translatedFormat('j M').' – '.$start->copy()->endOfWeek()->translatedFormat('j M');
        }

        return ['labels' => $labels, 'tips' => $tips, 'values' => array_map(fn ($v) => round($v, 1), $values)];
    }
}
