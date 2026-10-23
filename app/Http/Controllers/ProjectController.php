<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Support\ActiveInternship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Proyek mahasiswa: mengelompokkan task dan memberi kode seperti Jira (PMS-12). */
class ProjectController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);
        Gate::authorize('manageActivities', $internship);

        $request->merge(['key' => mb_strtoupper(trim((string) $request->input('key'))) ?: null]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:6', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('projects', 'key')->where('internship_id', $internship->id)],
            'color' => ['nullable', Rule::in(Project::COLORS)],
            'description' => ['nullable', 'string', 'max:1000'],
        ], ['key.regex' => 'Kode proyek hanya huruf dan angka.', 'key.unique' => 'Kode proyek sudah dipakai.'], ['name' => 'nama proyek', 'key' => 'kode proyek']);

        $key = ($data['key'] ?? null) ?: Project::suggestKey($data['name']);
        $base = $key;
        $n = 2;
        while ($internship->projects()->where('key', mb_strtoupper($key))->exists()) {
            $key = mb_substr($base, 0, 5).$n++;
        }

        $project = $internship->projects()->create([
            'name' => $data['name'],
            'key' => $key,
            'color' => $data['color'] ?? Project::COLORS[$internship->projects()->count() % count(Project::COLORS)],
            'description' => $data['description'] ?? null,
        ]);

        ActivityLog::record('proyek.buat', "Membuat proyek {$project->key} \"{$project->name}\"", $project);

        return back()->with('status', "Proyek {$project->name} ({$project->key}) dibuat.");
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship && (int) $project->internship_id === (int) $internship->id, 404);
        Gate::authorize('manageActivities', $internship);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'color' => ['sometimes', Rule::in(Project::COLORS)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'archived' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('archived', $data)) {
            $data['archived_at'] = $data['archived'] ? now() : null;
            unset($data['archived']);
        }

        $project->update($data);

        return back()->with('status', 'Proyek diperbarui.');
    }
}
