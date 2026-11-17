<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioCertificate;
use App\Models\PortfolioProject;
use App\Models\Skill;
use App\Models\StoredFile;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\Qr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portofolio mahasiswa: diisi sejak sebelum magang (profil, minat, IPK, skill, proyek, sertifikat pelatihan).
 * Dilihat oleh pemiliknya, pihak kampus, dosen pembimbing, Company Supervisor perusahaan terkait,
 * dan publik lewat tautan /p/{token} bila mahasiswa menyalakannya.
 */
class PortfolioController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('studentProfile.studyProgram', 'skills');
        $portfolio = Portfolio::forUser($user)->load(['projects.image', 'certificates.file']);

        return view('portofolio.edit', [
            'portfolio' => $portfolio,
            'user' => $user,
            'academic' => $user->academicIdentity(),
            'gpa' => $user->studentProfile?->gpa,
            'skills' => $user->skills->sortBy('name')->values(),
            'skillOptions' => Skill::orderBy('name')->pluck('name'),
            'internship' => $portfolio->latestInternship(),
            'qr' => $portfolio->is_public ? Qr::svg($portfolio->publicUrl()) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'is_public' => ['boolean'],
            'show_gpa' => ['boolean'],
            'headline' => ['nullable', 'string', 'max:120'],
            'about' => ['nullable', 'string', 'max:1000'],
            'gpa' => ['nullable', 'numeric', 'between:0,4'],
            'interests' => ['nullable', 'array', 'max:'.Portfolio::MAX_INTERESTS],
            'interests.*' => ['string', Rule::in(Portfolio::INTERESTS)],
            'skill_name' => ['nullable', 'array', 'max:'.Portfolio::MAX_SKILLS],
            'skill_name.*' => ['nullable', 'string', 'max:80'],
            'skill_level' => ['nullable', 'array'],
            'skill_level.*' => ['nullable', Rule::in(array_keys(Portfolio::LEVELS))],
        ], [
            'interests.max' => 'Pilih paling banyak '.Portfolio::MAX_INTERESTS.' minat.',
            'skill_name.max' => 'Paling banyak '.Portfolio::MAX_SKILLS.' skill.',
            'gpa.between' => 'IPK antara 0 dan 4.',
        ], ['gpa' => 'IPK']);

        DB::transaction(function () use ($user, $data, $request) {
            Portfolio::forUser($user)->update([
                'is_public' => $request->boolean('is_public'),
                'show_gpa' => $request->boolean('show_gpa'),
                'headline' => $data['headline'] ?? null,
                'about' => $data['about'] ?? null,
                'interests' => array_values(array_intersect(Portfolio::INTERESTS, $data['interests'] ?? [])),
            ]);

            StudentProfile::updateOrCreate(['user_id' => $user->id], ['gpa' => $data['gpa'] ?? null]);

            // Skill: nama dirapikan, tidak dobel; skill baru ditambahkan ke daftar umum.
            $sync = [];
            foreach ($data['skill_name'] ?? [] as $i => $name) {
                $name = mb_substr(trim(preg_replace('/\s+/', ' ', (string) $name)), 0, 80);
                if ($name === '') {
                    continue;
                }
                $skill = Skill::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first() ?? Skill::create(['name' => $name]);
                $sync[$skill->id] ??= ['level' => $data['skill_level'][$i] ?? 'menengah'];
            }
            $user->skills()->sync(array_slice($sync, 0, Portfolio::MAX_SKILLS, true));
        });

        return redirect()->route('portofolio.edit')->with('status', $request->boolean('is_public') ? 'Portofolio disimpan dan tautan publik aktif.' : 'Portofolio disimpan.');
    }

    /** Portofolio mahasiswa untuk pihak kampus, dosen pembimbing, dan Company Supervisor. */
    public function view(Request $request, User $student): Response
    {
        abort_unless($student->role === 'mahasiswa', 404);
        $portfolio = Portfolio::forUser($student);
        abort_unless($portfolio->canBeViewedBy($request->user()), 403);

        return response()->view('portofolio.publik', $this->pageData($portfolio, false) + ['back' => url()->previous()]);
    }

    /** Halaman publik tanpa login (hanya bila mahasiswa menyalakannya). */
    public function show(string $token): Response
    {
        $portfolio = Portfolio::where('token', $token)->where('is_public', true)->whereNotNull('user_id')->first();
        abort_unless($portfolio, 404, 'Portofolio tidak ditemukan atau tidak dibagikan.');

        return response()->view('portofolio.publik', $this->pageData($portfolio, true))->header('X-Robots-Tag', 'noindex');
    }

    /** Berkas proyek/sertifikat untuk pengguna yang boleh melihat portofolio. */
    public function file(Request $request, StoredFile $file): StreamedResponse
    {
        $portfolio = $this->portfolioOf($file);
        abort_unless($portfolio && $portfolio->canBeViewedBy($request->user()), 403);

        return $this->stream($file);
    }

    /** Berkas proyek/sertifikat di halaman publik. */
    public function publicFile(string $token, StoredFile $file): StreamedResponse
    {
        $portfolio = $this->portfolioOf($file);
        abort_unless($portfolio && $portfolio->is_public && hash_equals($portfolio->token, $token), 404);

        return $this->stream($file);
    }

    private function pageData(Portfolio $portfolio, bool $public): array
    {
        $portfolio->load(['projects.image', 'certificates.file', 'user.studentProfile.studyProgram', 'user.skills']);
        $student = $portfolio->user;
        $token = $portfolio->token;

        return [
            'portfolio' => $portfolio,
            'student' => $student,
            'academic' => $student->academicIdentity(),
            'gpa' => $portfolio->show_gpa ? $student->studentProfile?->gpa : null,
            'skills' => $student->skills->sortBy(fn ($s) => [array_search($s->pivot->level, ['mahir', 'menengah', 'pemula'], true), $s->name])->values(),
            'internship' => $portfolio->latestInternship(),
            'public' => $public,
            'fileUrl' => fn (StoredFile $f) => $public
                ? route('portofolio.publik.berkas', [$token, $f->id])
                : route('portofolio.berkas', $f->id),
        ];
    }

    private function portfolioOf(StoredFile $file): ?Portfolio
    {
        $id = PortfolioProject::where('image_file_id', $file->id)->value('portfolio_id')
            ?? PortfolioCertificate::where('file_id', $file->id)->value('portfolio_id');

        return $id ? Portfolio::find($id) : null;
    }

    private function stream(StoredFile $file): StreamedResponse
    {
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ], $file->opensInline() ? 'inline' : 'attachment');
    }
}
