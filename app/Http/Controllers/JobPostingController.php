<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Internship;
use App\Models\JobPosting;
use App\Models\Skill;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lowongan magang. TU Fakultas (semua perusahaan) dan Company Supervisor (perusahaannya sendiri) memasang lowongan;
 * mahasiswa melihat lowongan yang dibuka dan bisa langsung mengajukan KP dengan data perusahaan terisi.
 */
class JobPostingController extends Controller
{
    /** Pengelolaan (TU, supervisor). */
    public function manage(Request $request): View
    {
        $user = $request->user();

        return view('lowongan.kelola', [
            'postings' => $this->scope($user)->with(['company', 'skills'])->latest('id')->get(),
            'companies' => $this->companiesFor($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, 'posting');
        $posting = JobPosting::create($this->attributes($data) + ['posted_by' => $request->user()->id, 'status' => 'draft']);
        $this->syncSkills($posting, $data['keahlian'] ?? null);
        if ($request->boolean('buka')) {
            $this->open($posting);
        }
        ActivityLog::record('lowongan.buat', 'Membuat lowongan '.$posting->title.' di '.$posting->company->name, $posting);

        return redirect()->route('lowongan.kelola')->with('status', $posting->status === 'published' ? 'Lowongan dibuka dan terlihat mahasiswa.' : 'Lowongan tersimpan sebagai draf.');
    }

    public function update(Request $request, JobPosting $posting): RedirectResponse
    {
        $this->authorizeManage($request->user(), $posting);
        $data = $this->validated($request, 'posting'.$posting->id);
        $posting->update($this->attributes($data));
        $this->syncSkills($posting, $data['keahlian'] ?? null);

        return redirect()->route('lowongan.kelola')->with('status', 'Lowongan diperbarui.');
    }

    /** Buka (terbitkan) atau tutup lowongan. */
    public function status(Request $request, JobPosting $posting): RedirectResponse
    {
        $this->authorizeManage($request->user(), $posting);
        $to = $request->validate(['status' => ['required', Rule::in(['published', 'closed'])]])['status'];

        if ($to === 'published') {
            $this->open($posting);
        } else {
            $posting->update(['status' => 'closed']);
        }
        ActivityLog::record('lowongan.status', ($to === 'published' ? 'Membuka' : 'Menutup').' lowongan '.$posting->title, $posting);

        return back()->with('status', $to === 'published' ? 'Lowongan dibuka.' : 'Lowongan ditutup.');
    }

    public function destroy(Request $request, JobPosting $posting): RedirectResponse
    {
        $this->authorizeManage($request->user(), $posting);
        $posting->delete();

        return redirect()->route('lowongan.kelola')->with('status', 'Lowongan dihapus.');
    }

    /** Mahasiswa: lowongan yang sedang dibuka. */
    public function browse(Request $request): View
    {
        $active = Internship::where('student_id', $request->user()->id)->whereIn('status', ['diajukan', 'berjalan'])->exists();

        return view('lowongan.index', [
            'postings' => JobPosting::with(['company', 'skills'])->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today()))
                ->orderByRaw('deadline IS NULL')->orderBy('deadline')->get(),
            'canApply' => ! $active,
        ]);
    }

    private function open(JobPosting $posting): void
    {
        $first = $posting->published_at === null;
        $posting->update(['status' => 'published', 'published_at' => $posting->published_at ?? now()]);
        if ($first) {
            // Kabari mahasiswa yang belum punya KP aktif
            $students = User::where('role', 'mahasiswa')->where('is_active', true)
                ->whereDoesntHave('internships', fn ($q) => $q->whereIn('status', ['diajukan', 'berjalan', 'selesai']))->get();
            Notify::send($students, 'Lowongan magang baru: '.$posting->title, $posting->company->name, route('lowongan.index'), 'placement', 'green');
        }
    }

    /** @return Builder<JobPosting> */
    private function scope(User $user): Builder
    {
        return $user->role === 'tu'
            ? JobPosting::query()
            : JobPosting::whereIn('company_id', $this->companiesFor($user)->pluck('id'));
    }

    /** Perusahaan yang boleh dipasangi lowongan oleh pengguna ini. */
    private function companiesFor(User $user)
    {
        if ($user->role === 'tu') {
            return Company::orderBy('name')->get(['id', 'name', 'city']);
        }

        $ids = Internship::supervisedBy($user)->pluck('company_id')->filter()->unique();

        return Company::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'city']);
    }

    private function authorizeManage(User $user, JobPosting $posting): void
    {
        abort_unless($this->scope($user)->whereKey($posting->id)->exists(), 403);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $bag): array
    {
        $companyIds = $this->companiesFor($request->user())->pluck('id')->all();

        return $request->validateWithBag($bag, [
            'perusahaan' => ['required', Rule::in($companyIds)],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:3000'],
            'syarat' => ['nullable', 'string', 'max:2000'],
            'lokasi' => ['nullable', 'string', 'max:150'],
            'kuota' => ['nullable', 'integer', 'between:1,100'],
            'batas' => ['nullable', 'date', 'after_or_equal:today'],
            'keahlian' => ['nullable', 'string', 'max:300'],
        ], [
            'perusahaan.required' => 'Pilih perusahaan.',
            'perusahaan.in' => 'Perusahaan tidak bisa dipilih.',
            'judul.required' => 'Isi judul lowongan.',
            'deskripsi.required' => 'Tulis deskripsi pekerjaan.',
            'batas.after_or_equal' => 'Batas pendaftaran tidak boleh di masa lalu.',
        ]);
    }

    /** @return array<string, mixed> */
    private function attributes(array $data): array
    {
        return [
            'company_id' => $data['perusahaan'],
            'title' => $data['judul'],
            'description' => $data['deskripsi'],
            'requirements' => $data['syarat'] ?? null,
            'location' => $data['lokasi'] ?? null,
            'quota' => $data['kuota'] ?? null,
            'deadline' => $data['batas'] ?? null,
        ];
    }

    private function syncSkills(JobPosting $posting, ?string $skills): void
    {
        $ids = collect(explode(',', (string) $skills))->map(fn ($s) => trim($s))->filter()->unique(fn ($s) => mb_strtolower($s))->take(8)
            ->map(fn ($name) => (Skill::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first() ?? Skill::create(['name' => mb_substr($name, 0, 80)]))->id);
        $posting->skills()->sync($ids->all());
    }
}
