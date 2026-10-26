<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Goal;
use App\Models\Internship;
use App\Support\ActiveInternship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Goals di halaman Kegiatan: tujuan yang ingin dicapai mahasiswa selama KP. */
class GoalController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $internship = $this->internship($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [], ['title' => 'goal', 'description' => 'keterangan']);

        $goal = $internship->goals()->create($data + [
            'position' => (int) $internship->goals()->max('position') + 1,
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::record('goal.buat', "Menambah goal \"{$goal->title}\"", $goal);

        return back()->with('status', 'Goal ditambahkan.');
    }

    public function update(Request $request, Goal $goal): RedirectResponse
    {
        $internship = $this->internship($request, $goal);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ], [], ['title' => 'goal']);

        // Status tercapai tidak diisi manual: mengikuti task (Goal::syncAchievement).
        $goal->update($data);

        return back()->with('status', 'Goal diperbarui.');
    }

    public function destroy(Request $request, Goal $goal): RedirectResponse
    {
        $this->internship($request, $goal);

        $title = $goal->title;
        $goal->delete(); // task dan rencana yang terkait tetap ada, kaitannya dilepas

        ActivityLog::record('goal.hapus', "Menghapus goal \"{$title}\"");

        return back()->with('status', 'Goal dihapus.');
    }

    private function internship(Request $request, ?Goal $goal = null): Internship
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship && (! $goal || (int) $goal->internship_id === (int) $internship->id), 404);
        Gate::authorize('manageActivities', $internship);

        return $internship;
    }
}
