<?php

namespace App\Http\Controllers;

use App\Actions\Reports\DiscussReport;
use App\Actions\Reports\ManageReportChapters;
use App\Models\Internship;
use App\Models\ReportChapter;
use App\Models\ReportComment;
use App\Models\ReportScore;
use App\Models\StoredFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Halaman laporan KP bersama mahasiswa dan dosen pembimbing: diskusi per bab, laporan akhir, dan nilainya. */
class ReportDiscussionController extends Controller
{
    private const FILE_RULES = ['file', 'max:'.ManageReportChapters::MAX_KB, 'extensions:pdf,docx',
        'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream'];

    private const FILE_MESSAGES = [
        'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
        'berkas.extensions' => 'Berkas harus PDF atau Word (.docx).',
        'berkas.mimetypes' => 'Isi berkas harus PDF atau Word (.docx).',
    ];

    public function show(Request $request, Internship $internship): View
    {
        $user = $request->user();
        abort_unless(DiscussReport::canDiscuss($user, $internship), 403);

        $chapters = ManageReportChapters::ensureFor($internship);
        $chapters->each->setRelation('internship', $internship);
        $internship->load(['student', 'lecturer', 'finalReport.file', 'reportScore', 'defenseScore', 'reportComments.user', 'reportComments.file']);
        $threads = $internship->reportComments->groupBy(fn (ReportComment $c) => $c->report_chapter_id ?? 'akhir');

        return view('laporan.diskusi', [
            'internship' => $internship,
            'chapters' => $chapters,
            'threads' => $threads,
            'isStudent' => (int) $user->id === (int) $internship->student_id,
            'isLecturer' => $user->role === 'dospem',
            'locked' => $internship->defenseScore?->lecturer_score !== null,
        ]);
    }

    public function comment(Request $request, Internship $internship, DiscussReport $discuss): RedirectResponse
    {
        $data = $request->validate([
            'bab' => ['nullable', 'integer'],
            'isi' => ['nullable', 'string', 'max:2000'],
            'berkas' => ['nullable', ...self::FILE_RULES],
        ], self::FILE_MESSAGES);

        $chapter = filled($data['bab'] ?? null) ? ReportChapter::where('internship_id', $internship->id)->findOrFail($data['bab']) : null;
        $discuss->comment($request->user(), $internship->loadMissing(['student', 'lecturer']), $chapter, $data['isi'] ?? null, $request->file('berkas'));

        return redirect()->to(route('laporan.diskusi', $internship).($chapter ? '#bab-'.$chapter->position : '#akhir'))
            ->with('status', 'Komentar terkirim.');
    }

    public function uploadFinal(Request $request, Internship $internship, DiscussReport $discuss): RedirectResponse
    {
        $request->validate(['berkas' => ['required', ...self::FILE_RULES]], ['berkas.required' => 'Pilih berkas laporan akhir.'] + self::FILE_MESSAGES);
        $discuss->submitFinal($request->user(), $internship->loadMissing(['student', 'lecturer', 'defenseScore']), $request->file('berkas'));

        return redirect()->to(route('laporan.diskusi', $internship).'#akhir')->with('status', 'Laporan akhir terunggah dan diberitahukan ke dosen pembimbing.');
    }

    public function grade(Request $request, Internship $internship, DiscussReport $discuss): RedirectResponse
    {
        $rules = collect(ReportScore::ASPECTS)->mapWithKeys(fn ($a, $key) => [$key => ['required', 'integer', 'between:0,'.$a[1]]])->all();
        $messages = collect(ReportScore::ASPECTS)->mapWithKeys(fn ($a, $key) => [
            $key.'.required' => 'Isi nilai '.mb_strtolower($a[0]).'.',
            $key.'.between' => 'Nilai '.mb_strtolower($a[0]).' antara 0 dan '.$a[1].'.',
        ])->all();
        $data = $request->validate($rules + ['note' => ['nullable', 'string', 'max:1000']], $messages);

        $score = $discuss->grade($request->user(), $internship->loadMissing(['student', 'finalReport', 'defenseScore']), $data);

        return redirect()->to(route('laporan.diskusi', $internship).'#nilai')->with('status', 'Nilai laporan akhir tersimpan: '.$score->total.'.');
    }

    public function commentFile(Request $request, ReportComment $comment): StreamedResponse
    {
        abort_unless(DiscussReport::canDiscuss($request->user(), $comment->internship), 403);

        return $this->stream($comment->file);
    }

    /** Laporan akhir: mahasiswa, dosen pembimbing, dan kaprodi (untuk pengesahan nilai sidang). */
    public function finalFile(Request $request, Internship $internship): StreamedResponse
    {
        $user = $request->user();
        abort_unless(DiscussReport::canDiscuss($user, $internship) || $user->role === 'kaprodi', 403);

        return $this->stream($internship->finalReport?->file);
    }

    private function stream(?StoredFile $file): StreamedResponse
    {
        abort_unless($file && Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
