<?php

namespace App\Http\Controllers;

use App\Actions\Activities\LogTime;
use App\Http\Requests\Activities\SaveTimeEntryRequest;
use App\Models\TimeEntry;
use App\Support\ActiveInternship;
use App\Support\Gantt;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Timesheet mahasiswa: rencana kegiatan dalam bentuk Gantt (diisi lebih dulu, tanpa jam),
 * lalu catatan kegiatan harian (tanggal, kegiatan, task, foto dokumentasi).
 */
class TimesheetController extends Controller
{
    public function index(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['company']);

        if (! $internship) {
            return view('timesheet.index', ['internship' => null]);
        }

        $offset = max(-60, min(0, $request->integer('p')));
        $all = $request->query('periode') === 'semua';
        $period = Period::for($internship, null, $offset);

        $query = $internship->timeEntries()->with(['task.project', 'photo'])->orderByDesc('work_date')->orderByDesc('id');
        if (! $all) {
            $query->whereBetween('work_date', [$period->start->toDateString(), $period->end->toDateTimeString()]);
        }
        $entries = $query->get();

        $plans = $internship->activityPlans()
            ->with(['goal', 'tasks' => fn ($q) => $q->with('column')->orderByRaw('COALESCE(start_date, due_date) IS NULL')->orderByRaw('COALESCE(start_date, due_date)')->orderBy('number')])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed')])
            ->get();

        return view('timesheet.index', [
            'internship' => $internship,
            'plans' => $plans,
            'gantt' => Gantt::build($internship, $plans),
            'goals' => $internship->goals()->get(),
            'entries' => $entries,
            'days' => $entries->groupBy(fn (TimeEntry $e) => $e->work_date->toDateString()),
            'period' => $period,
            'offset' => $offset,
            'all' => $all,
            'tasks' => $internship->tasks()->with('project')->where('status', '!=', 'completed')->orderByDesc('updated_at')->limit(50)->get(),
            'canEdit' => $request->user()->can('manageActivities', $internship),
        ]);
    }

    public function store(SaveTimeEntryRequest $request, LogTime $log): RedirectResponse
    {
        $log->create($request->user(), $request->internship(), $request->validated(), $request->file('foto'));

        return back()->with('status', 'Catatan kegiatan tersimpan.');
    }

    public function update(SaveTimeEntryRequest $request, TimeEntry $entry, LogTime $log): RedirectResponse
    {
        $log->update($request->user(), $entry, $request->validated(), $request->file('foto'));

        return back()->with('status', 'Catatan kegiatan diperbarui.');
    }

    public function destroy(Request $request, TimeEntry $entry, LogTime $log): RedirectResponse
    {
        abort_unless((int) $entry->created_by === (int) $request->user()->id, 403);
        Gate::authorize('manageActivities', $entry->internship);

        $log->delete($entry);

        return back()->with('status', 'Catatan kegiatan dihapus.');
    }

    /** Foto dokumentasi hanya untuk yang boleh melihat kegiatan mahasiswa itu. */
    public function photo(Request $request, TimeEntry $entry): StreamedResponse
    {
        $internship = $entry->internship;
        $user = $request->user();

        $allowed = match ($user->role) {
            'mahasiswa' => (int) $internship->student_id === (int) $user->id,
            'supervisor' => $internship->isSupervisedBy($user),
            'dospem' => (int) $internship->lecturer_id === (int) $user->id,
            'kaprodi' => true,
            default => false,
        };
        abort_unless($allowed, 403);

        $file = $entry->photo;
        abort_unless($file && Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
