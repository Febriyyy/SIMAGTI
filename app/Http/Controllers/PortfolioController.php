<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Support\ActiveInternship;
use App\Support\Qr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Portofolio KP: pengaturan oleh mahasiswa (nyala/mati tautan publik, judul singkat, tentang, keahlian,
 * pilihan hasil kerja, tampilkan nilai) dan halaman publik tanpa login (/p/{token}, tanpa NIM).
 */
class PortfolioController extends Controller
{
    public function edit(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['company', 'assessment']);
        abort_unless($internship, 404, 'Belum ada KP berjalan.');

        $portfolio = Portfolio::for($internship);

        return view('portofolio.edit', [
            'internship' => $internship,
            'portfolio' => $portfolio,
            'tasks' => $internship->tasks()->where('status', 'completed')->with('plan')->orderByDesc('completed_at')->get(),
            'hasGrade' => (bool) ($internship->final_grade || $internship->assessment?->isValidated()),
            'qr' => $portfolio->is_public ? Qr::svg($portfolio->publicUrl()) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);

        $data = $request->validate([
            'is_public' => ['boolean'],
            'show_grade' => ['boolean'],
            'headline' => ['nullable', 'string', 'max:120'],
            'about' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'string', 'max:300'],
            'task_ids' => ['nullable', 'array', 'max:'.Portfolio::MAX_TASKS],
            'task_ids.*' => ['integer'],
        ], [
            'task_ids.max' => 'Pilih paling banyak '.Portfolio::MAX_TASKS.' hasil kerja.',
        ]);

        $allowed = $internship->tasks()->where('status', 'completed')->pluck('id')->all();
        $skills = collect(explode(',', (string) ($data['skills'] ?? '')))->map(fn ($s) => trim($s))->filter()->unique()->take(Portfolio::MAX_SKILLS)->values()->all();

        Portfolio::for($internship)->update([
            'is_public' => $request->boolean('is_public'),
            'show_grade' => $request->boolean('show_grade'),
            'headline' => $data['headline'] ?? null,
            'about' => $data['about'] ?? null,
            'skills' => $skills,
            'task_ids' => array_values(array_intersect(array_map('intval', $data['task_ids'] ?? []), $allowed)),
        ]);

        return redirect()->route('portofolio.edit')->with('status', $request->boolean('is_public') ? 'Portofolio disimpan dan tautan publik aktif.' : 'Portofolio disimpan. Tautan publik nonaktif.');
    }

    public function show(string $token): Response
    {
        $portfolio = Portfolio::where('token', $token)->where('is_public', true)
            ->with('internship.student', 'internship.company', 'internship.division', 'internship.assessment', 'internship.certificate')
            ->first();
        abort_unless($portfolio, 404, 'Portofolio tidak ditemukan atau tidak dibagikan.');

        $internship = $portfolio->internship;
        $tasks = $internship->tasks()->whereIn('id', $portfolio->task_ids ?? [])->where('status', 'completed')->with('plan')->get()
            ->sortBy(fn ($t) => array_search($t->id, $portfolio->task_ids ?? [], true))->values();
        $allTasks = $internship->tasks()->get(['id', 'status']);
        $assessment = $internship->assessment;
        $grade = $portfolio->show_grade ? ($internship->final_grade ?? ($assessment?->isValidated() ? $assessment->letter_grade : null)) : null;

        return response()->view('portofolio.publik', [
            'portfolio' => $portfolio,
            'internship' => $internship,
            'student' => $internship->student,
            'academic' => $internship->student->academicIdentity(),
            'tasks' => $tasks,
            'goals' => $internship->goals()->whereNotNull('achieved_at')->get(),
            'stats' => [
                'days' => $internship->timeEntries()->distinct()->count('work_date'),
                'done' => $allTasks->where('status', 'completed')->count(),
                'tasks' => $allTasks->count(),
                'weeks' => $internship->totalWeeks(),
            ],
            'grade' => $grade,
            'score' => $grade ? ($internship->final_score ?? $assessment?->final_score) : null,
            'certificate' => $internship->certificate?->isValid() ? $internship->certificate : null,
        ])->header('X-Robots-Tag', 'noindex');
    }
}
