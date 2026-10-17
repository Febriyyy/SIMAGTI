<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use App\Models\WeeklyMeeting;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            'mahasiswa' => $this->mahasiswa($user),
            'mentor' => $this->mentor($user),
            'biro' => $this->biro($user),
            default => $this->pantau($user), // ketua_divisi, dospem, kaprodi
        };
    }

    private function mahasiswa(User $user): View
    {
        $internship = Internship::with(['company', 'mentor', 'supervisor', 'lecturer'])
            ->withProgress()
            ->where('student_id', $user->id)
            ->latest('start_date')
            ->first();

        $tasks = collect();
        $meeting = null;
        $weekly = collect();

        if ($internship) {
            $week = $internship->currentWeek();

            $tasks = $internship->tasks()
                ->withCount(['evidences', 'comments'])
                ->where('week_number', $week)
                ->get()
                ->sortBy(fn (WeeklyTask $task) => $task->dayOrder())
                ->values();

            $meeting = $internship->currentMeeting();

            // Task selesai per minggu (maksimal 5 minggu terakhir)
            $weekly = $internship->tasks()
                ->selectRaw("week_number, COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as done")
                ->where('week_number', '<=', $week)
                ->groupBy('week_number')
                ->orderBy('week_number')
                ->get()
                ->take(-5)
                ->values();
        }

        return view('dashboard.mahasiswa', compact('user', 'internship', 'tasks', 'meeting', 'weekly'));
    }

    private function mentor(User $user): View
    {
        $internships = Internship::with(['student', 'company'])
            ->withProgress()
            ->visibleTo($user)
            ->where('status', 'berjalan')
            ->get();

        $ids = $internships->pluck('id');

        $reviewQuery = WeeklyTask::whereIn('internship_id', $ids)
            ->has('evidences')
            ->doesntHave('comments');

        $stats = [
            'students' => $internships->count(),
            'review' => (clone $reviewQuery)->count(),
            'done' => (int) $internships->sum('completed_tasks_count'),
        ];

        $review = $reviewQuery->with('internship.student')->latest('updated_at')->limit(5)->get();

        // Kelompokkan meeting minggu ini: mahasiswa yang jadwalnya sama digabung
        $meetings = WeeklyMeeting::whereIn('internship_id', $ids)->get()
            ->keyBy(fn (WeeklyMeeting $m) => $m->internship_id.'-'.$m->week_number);

        $groups = [];
        $withoutMeeting = [];
        foreach ($internships as $internship) {
            $m = $meetings->get($internship->id.'-'.$internship->currentWeek());
            if ($m) {
                $key = $m->day.'|'.$m->time.'|'.$m->url;
                $groups[$key] ??= ['meeting' => $m, 'students' => []];
                $groups[$key]['students'][] = $internship->student;
            } else {
                $withoutMeeting[] = $internship->student;
            }
        }
        $groups = collect($groups)
            ->sortBy(fn (array $g) => $g['meeting']->dayOrder() * 10000 + (int) str_replace(':', '', $g['meeting']->time))
            ->values();

        return view('dashboard.mentor', compact('user', 'internships', 'stats', 'review', 'groups', 'withoutMeeting'));
    }

    /** Ketua divisi, dosen pembimbing, dan kaprodi: daftar pemantauan. */
    private function pantau(User $user): View
    {
        $internships = Internship::with(['student', 'company', 'mentor'])
            ->withProgress()
            ->visibleTo($user)
            ->orderBy('status')
            ->orderBy('start_date')
            ->get();

        $headings = [
            'ketua_divisi' => ['Mahasiswa magang di divisi Anda.'],
            'dospem' => ['Mahasiswa bimbingan Anda.'],
            'kaprodi' => ['Seluruh mahasiswa kerja praktik.'],
        ];
        $subtitle = $headings[$user->role][0] ?? '';

        $stats = [
            'active' => $internships->where('status', 'berjalan')->count(),
            'companies' => $internships->pluck('company_id')->unique()->count(),
            'average' => $internships->count() > 0
                ? (int) round($internships->avg(fn (Internship $i) => $i->percent()))
                : 0,
            'finished' => $internships->where('status', 'selesai')->count(),
        ];

        // Tiga mahasiswa aktif dengan progres task terendah
        $attention = $internships
            ->where('status', 'berjalan')
            ->sortBy(fn (Internship $i) => $i->percent())
            ->take(3)
            ->values();

        return view('dashboard.pantau', compact('user', 'internships', 'stats', 'subtitle', 'attention'));
    }

    private function biro(User $user): View
    {
        $companies = Company::withCount('internships')->orderBy('name')->get();

        $stats = [
            'partners' => $companies->whereIn('status', ['mitra', 'mandiri_terverifikasi'])->count(),
            'waiting' => $companies->where('status', 'mandiri_menunggu')->count(),
            'students' => Internship::where('status', 'berjalan')->count(),
        ];

        $waiting = $companies->where('status', 'mandiri_menunggu')->values();

        return view('dashboard.biro', compact('user', 'companies', 'stats', 'waiting'));
    }
}
