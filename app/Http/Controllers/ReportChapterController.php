<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ManageReportChapters;
use App\Models\Internship;
use App\Models\ReportChapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Laporan KP per bab: unggah oleh mahasiswa, tinjauan Company Supervisor dan dosen pembimbing. */
class ReportChapterController extends Controller
{
    public function upload(Request $request, ReportChapter $chapter, ManageReportChapters $reports): RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'max:'.ManageReportChapters::MAX_KB, 'extensions:pdf,docx',
                'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream'],
        ], [
            'berkas.required' => 'Pilih berkas bab yang akan diunggah.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
            'berkas.extensions' => 'Berkas harus PDF atau Word (.docx).',
            'berkas.mimetypes' => 'Isi berkas harus PDF atau Word (.docx).',
        ]);

        $chapter = $reports->submit($request->user(), $chapter->load('internship.student'), $request->file('berkas'));
        $to = $chapter->needs_supervisor ? 'Company Supervisor' : 'dosen pembimbing';

        return redirect()->to(route('dokumen.index').'#laporan')->with('status', "{$chapter->label()} terunggah dan menunggu tinjauan {$to}.");
    }

    /** Antrean tinjauan untuk Company Supervisor dan dosen pembimbing, plus progres laporan tiap mahasiswa. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $internships = Internship::with(['student', 'reportChapters.file'])
            ->visibleTo($user)->whereIn('status', ['berjalan', 'selesai'])
            ->orderBy('end_date')->get();

        $waitingStatus = $user->role === 'supervisor' ? 'menunggu_supervisor' : 'menunggu_dosen';
        $queue = $internships->flatMap(fn (Internship $i) => $i->reportChapters->where('status', $waitingStatus)->each->setRelation('internship', $i))
            ->sortBy('submitted_at')->values();

        return view('laporan.antrean', [
            'queue' => $queue,
            'internships' => $internships,
            'isSupervisor' => $user->role === 'supervisor',
        ]);
    }

    public function review(Request $request, ReportChapter $chapter, ManageReportChapters $reports): RedirectResponse
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:setujui,revisi'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $reports->review($request->user(), $chapter->load('internship.student'), $data['keputusan'] === 'setujui', $data['catatan'] ?? null);

        return back()->with('status', $data['keputusan'] === 'setujui' ? "{$chapter->label()} disetujui." : "Catatan revisi {$chapter->label()} dikirim ke mahasiswa.");
    }

    public function file(Request $request, ReportChapter $chapter): StreamedResponse
    {
        abort_unless($chapter->canBeReadBy($request->user()), 403);
        $file = $chapter->file;
        abort_unless($file && Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
