<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildMonitoringDashboard;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\InternshipDocument;
use App\Models\InternshipStaff;
use App\Models\Invitation;
use App\Models\RubricAspect;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WeeklyMeeting;
use App\Models\WeeklyTask;
use App\Support\Gantt;
use App\Support\KpProgress;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            'mahasiswa' => $this->mahasiswa($user),
            'supervisor' => $this->supervisor($user),
            'tu' => $this->tu($user),
            'admin' => $this->admin($user),
            default => $this->pantau($user), // dospem, kaprodi
        };
    }

    private function mahasiswa(User $user): View
    {
        $internship = Internship::with(['company', 'division', 'supervisor', 'lecturer', 'assessment'])
            ->withProgress()
            ->where('student_id', $user->id)
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 WHEN 'diajukan' THEN 1 WHEN 'selesai' THEN 2 ELSE 3 END")
            ->latest('start_date')
            ->first();

        $data = ['user' => $user, 'internship' => $internship, 'tasks' => collect(), 'meeting' => null,
            'period' => null, 'next' => null, 'steps' => [], 'plans' => collect(), 'gantt' => null, 'goals' => collect()];

        if (! $internship) {
            return view('dashboard.mahasiswa', $data);
        }

        $data['steps'] = KpProgress::steps($internship);
        $data['next'] = KpProgress::current($internship, $data['steps']);

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
            $data['meeting'] = $internship->currentMeeting();
            $data['plans'] = $internship->activityPlans()
                ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed')])
                ->get();
            $data['gantt'] = Gantt::build($internship, $data['plans'], withTasks: false);
            $data['goals'] = $internship->goals()->get();
        }

        return view('dashboard.mahasiswa', $data);
    }

    /** Company Supervisor: memantau mahasiswa magang, mengatur meeting, menilai di akhir periode KP. */
    private function supervisor(User $user): View
    {
        $internships = Internship::with(['student.studentProfile.studyProgram', 'company', 'division', 'assessment'])
            ->withProgress()
            ->withCount('activityPlans')
            ->supervisedBy($user)
            ->where('status', 'berjalan')
            ->orderBy('start_date')
            ->get();

        $ids = $internships->pluck('id');
        $weekStart = Carbon::today()->startOfWeek();

        // Catatan kegiatan minggu ini per mahasiswa (Senin sampai hari ini)
        $weekNotes = TimeEntry::whereIn('internship_id', $ids)
            ->where('work_date', '>=', $weekStart->toDateString())
            ->selectRaw('internship_id, COUNT(*) as total')
            ->groupBy('internship_id')
            ->pluck('total', 'internship_id');

        $stats = [
            'students' => $internships->count(),
            'unconfirmed' => $internships->whereNull('supervisor_confirmation')->count(),
            'week_notes' => (int) $weekNotes->sum(),
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

        return view('dashboard.supervisor', compact('user', 'internships', 'stats', 'statusCounts', 'recent', 'groups', 'withoutMeeting', 'weekNotes'));
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
            'running' => Internship::where('status', 'berjalan')->count(),
        ];

        $recent = InternshipStaff::with(['user', 'internship.student', 'internship.company'])
            ->where('role', InternshipStaff::ROLE_SUPERVISOR)
            ->latest('id')
            ->limit(5)
            ->get();

        // Data perusahaan (sebelumnya di Biro Kerjasama): urut dari yang paling banyak mahasiswa magang
        $companies = Company::withCount([
            'internships',
            'internships as active_internships_count' => fn ($q) => $q->where('status', 'berjalan'),
        ])
            ->withAvg(['assessments as average_score' => fn ($q) => $q->where('assessments.status', 'validated')], 'final_score')
            ->orderByDesc('active_internships_count')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $stats['verify'] = Internship::where('status', 'diajukan')
            ->whereHas('approvals', fn ($q) => $q->where('step', InternshipApproval::STEP_VERIFY)->where('status', 'pending'))
            ->count();
        $stats['letters'] = InternshipDocument::where('type', InternshipDocument::LETTER)->where('status', 'diminta')->count();
        $stats['partners'] = Company::whereIn('status', ['mitra', 'mandiri_terverifikasi'])->count();

        return view('dashboard.tu', compact('user', 'groups', 'stats', 'recent', 'companies'));
    }

    /** Admin: ringkasan akun dan aktivitas sistem terbaru. */
    private function admin(User $user): View
    {
        $roles = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        $stats = [
            'users' => (int) $roles->sum(),
            'inactive' => User::where('is_active', false)->count(),
            'questions' => RubricAspect::whereHas('rubric', fn ($q) => $q->where('is_active', true)->whereNull('owner_id'))->count(),
            'logs_today' => ActivityLog::whereDate('created_at', Carbon::today())->count(),
        ];

        $logs = ActivityLog::with('user')->latest('id')->limit(8)->get();

        return view('dashboard.admin', compact('user', 'roles', 'stats', 'logs'));
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
}
