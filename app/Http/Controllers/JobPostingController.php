<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Internship;
use App\Models\JobPosting;
use App\Models\Portfolio;
use App\Models\Skill;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lowongan magang. TU Fakultas (semua perusahaan) membuka lowongan langsung; Company Supervisor (perusahaannya sendiri)
 * mengajukan lowongan yang lalu disetujui atau ditolak TU Fakultas;
 * mahasiswa melihat lowongan yang dibuka dan bisa langsung mengajukan KP dengan data perusahaan terisi.
 */
class JobPostingController extends Controller
{
    /** Pengelolaan (TU, supervisor). */
    public function manage(Request $request): View
    {
        $user = $request->user();

        $companies = $this->companiesFor($user);

        return view('lowongan.kelola', [
            // Ajuan yang menunggu persetujuan TU tampil paling atas
            'postings' => $this->scope($user)->with(['company', 'skills', 'poster'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'rejected' THEN 1 ELSE 2 END")->latest('id')->get(),
            'companies' => $companies,
            // Mahasiswa yang sedang mengajukan KP ke perusahaan ini; portofolionya bisa dibuka sebelum diterima.
            'applicants' => Internship::with(['student.studentProfile.studyProgram', 'company'])
                ->where('status', 'diajukan')->whereIn('company_id', $companies->pluck('id'))
                ->latest('id')->limit(30)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validated($request, 'posting');
        $posting = JobPosting::create($this->attributes($data) + ['posted_by' => $user->id, 'status' => 'draft']);
        $this->syncSkills($posting, $data['keahlian'] ?? null);
        ActivityLog::record('lowongan.buat', 'Membuat lowongan '.$posting->title.' di '.$posting->company->name, $posting);

        if (! $request->boolean('buka')) {
            return redirect()->route('lowongan.kelola')->with('status', 'Lowongan tersimpan sebagai draf.');
        }

        return redirect()->route('lowongan.kelola')->with('status', $this->publishOrSubmit($user, $posting));
    }

    public function update(Request $request, JobPosting $posting): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeManage($user, $posting);
        $data = $this->validated($request, 'posting'.$posting->id);
        $posting->update($this->attributes($data));
        $this->syncSkills($posting, $data['keahlian'] ?? null);

        // Lowongan supervisor yang sudah dibuka lalu diubah ditinjau ulang TU, agar isinya tidak berganti diam-diam
        if ($user->role !== 'tu' && $posting->status === 'published') {
            return redirect()->route('lowongan.kelola')->with('status', 'Lowongan diperbarui. '.$this->publishOrSubmit($user, $posting));
        }

        return redirect()->route('lowongan.kelola')->with('status', 'Lowongan diperbarui.');
    }

    /** Buka (TU langsung; supervisor mengajukan ke TU) atau tutup lowongan. */
    public function status(Request $request, JobPosting $posting): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeManage($user, $posting);
        $to = $request->validate(['status' => ['required', Rule::in(['published', 'closed'])]])['status'];

        if ($to === 'published') {
            return back()->with('status', $this->publishOrSubmit($user, $posting));
        }

        $posting->update(['status' => 'closed']);
        ActivityLog::record('lowongan.status', 'Menutup lowongan '.$posting->title, $posting);

        return back()->with('status', 'Lowongan ditutup.');
    }

    /** TU Fakultas menyetujui (lowongan dibuka) atau menolak dengan alasan lowongan ajuan supervisor. */
    public function review(Request $request, JobPosting $posting): RedirectResponse
    {
        abort_unless($posting->status === 'pending', 404);
        $data = $request->validateWithBag('review'.$posting->id, [
            'keputusan' => ['required', Rule::in(['setujui', 'tolak'])],
            'catatan' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('keputusan') === 'tolak')],
        ], ['catatan.required' => 'Tulis alasan agar supervisor bisa memperbaikinya.']);

        $approve = $data['keputusan'] === 'setujui';
        $posting->update([
            'review_note' => filled($data['catatan'] ?? null) ? trim($data['catatan']) : null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ] + ($approve ? [] : ['status' => 'rejected']));
        if ($approve) {
            $this->open($posting);
        }

        ActivityLog::record('lowongan.tinjau', ($approve ? 'Menyetujui' : 'Menolak').' lowongan '.$posting->title, $posting);
        Notify::send($posting->poster, $approve ? 'Lowongan disetujui: '.$posting->title : 'Lowongan perlu perbaikan: '.$posting->title,
            $approve ? 'Lowongan sudah dibuka untuk mahasiswa.' : $posting->review_note, route('lowongan.kelola'), 'placement', $approve ? 'green' : 'red');

        return back()->with('status', $approve ? 'Lowongan disetujui dan dibuka.' : 'Lowongan dikembalikan ke supervisor.');
    }

    /** TU membuka langsung; Company Supervisor mengajukan ke TU Fakultas. @return string pesan untuk pengguna */
    private function publishOrSubmit(User $user, JobPosting $posting): string
    {
        if ($user->role === 'tu') {
            $this->open($posting);
            ActivityLog::record('lowongan.status', 'Membuka lowongan '.$posting->title, $posting);

            return 'Lowongan dibuka dan terlihat mahasiswa.';
        }

        $posting->update(['status' => 'pending', 'submitted_at' => now(), 'review_note' => null, 'reviewed_by' => null, 'reviewed_at' => null]);
        ActivityLog::record('lowongan.ajukan', 'Mengajukan lowongan '.$posting->title.' ke TU Fakultas', $posting);
        Notify::send(User::where('role', 'tu')->where('is_active', true)->get(), 'Lowongan menunggu persetujuan: '.$posting->title,
            $posting->company->name, route('lowongan.kelola'), 'placement', 'amber');

        return 'Lowongan diajukan ke TU Fakultas dan dibuka setelah disetujui.';
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

        $user = $request->user();

        return view('lowongan.index', [
            'postings' => JobPosting::with(['company', 'skills'])->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today()))
                ->orderByRaw('deadline IS NULL')->orderBy('deadline')->get(),
            'canApply' => ! $active,
            // Skill mahasiswa (dari portofolio) untuk menandai lowongan yang cocok
            'mySkills' => $user->skills()->pluck('skills.id')->all(),
            'hasPortfolio' => $user->skills()->exists() || Portfolio::where('user_id', $user->id)->whereNotNull('about')->exists(),
            // Ulasan alumni magang per perusahaan (tanpa nama)
            'reviews' => CompanyReview::latest()->get(['company_id', 'mentoring', 'work_fit', 'environment', 'rating', 'recommend', 'comment', 'created_at'])
                ->groupBy('company_id')->map(fn ($r) => CompanyReview::summarize($r) + ['comments' => $r->whereNotNull('comment')->take(3)->pluck('comment')->all()]),
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
