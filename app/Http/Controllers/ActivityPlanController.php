<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ActivityPlan;
use App\Models\Internship;
use App\Support\ActiveInternship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Rencana kegiatan di Timesheet (Gantt). Mahasiswa mengisi rencana dulu (nama kegiatan dan
 * rentang tanggal, mingguan atau bulanan sesuai lama pengerjaan), baru membuat task di bawahnya.
 */
class ActivityPlanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $internship = $this->internship($request);
        $data = $this->validated($request, $internship);

        $plan = $internship->activityPlans()->create($data + [
            'position' => (int) $internship->activityPlans()->max('position') + 1,
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::record('rencana.buat', "Menyusun rencana \"{$plan->title}\" ({$plan->rangeLabel()})", $plan);

        return back()->with('status', 'Rencana kegiatan ditambahkan. Sekarang task bisa dibuat di bawah rencana ini.');
    }

    public function update(Request $request, ActivityPlan $plan): RedirectResponse
    {
        $internship = $this->internship($request, $plan);
        $plan->update($this->validated($request, $internship, $plan));

        ActivityLog::record('rencana.ubah', "Mengubah rencana \"{$plan->title}\" ({$plan->rangeLabel()})", $plan);

        return back()->with('status', 'Rencana kegiatan diperbarui.');
    }

    public function destroy(Request $request, ActivityPlan $plan): RedirectResponse
    {
        $this->internship($request, $plan);

        if ($plan->tasks()->exists()) {
            return back()->withErrors(['plan' => 'Rencana ini sudah punya task. Pindahkan atau hapus task-nya dulu.']);
        }

        $title = $plan->title;
        $plan->delete();
        ActivityLog::record('rencana.hapus', "Menghapus rencana \"{$title}\"");

        return back()->with('status', 'Rencana kegiatan dihapus.');
    }

    private function validated(Request $request, Internship $internship, ?ActivityPlan $plan = null): array
    {
        $creating = $plan === null;
        $start = $internship->start_date->toDateString();
        $end = $internship->end_date->toDateString();

        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'start_date' => [$creating ? 'required' : 'sometimes', 'date', 'after_or_equal:'.$start, 'before_or_equal:'.$end],
            'end_date' => [$creating ? 'required' : 'sometimes', 'date', 'after_or_equal:start_date', 'before_or_equal:'.$end],
            'goal_id' => ['sometimes', 'nullable', 'integer', Rule::exists('goals', 'id')->where('internship_id', $internship->id)],
            'color' => ['sometimes', 'nullable', Rule::in(array_keys(ActivityPlan::COLORS))],
        ], [
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum KP dimulai.',
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh setelah KP berakhir.',
            'end_date.before_or_equal' => 'Tanggal selesai tidak boleh setelah KP berakhir.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ], ['title' => 'nama kegiatan', 'start_date' => 'tanggal mulai', 'end_date' => 'tanggal selesai', 'goal_id' => 'goal']);
    }

    private function internship(Request $request, ?ActivityPlan $plan = null): Internship
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship && (! $plan || (int) $plan->internship_id === (int) $internship->id), 404);
        Gate::authorize('manageActivities', $internship);

        return $internship;
    }
}
