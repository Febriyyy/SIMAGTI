<?php

namespace App\Http\Controllers;

use App\Actions\Documents\ManageDocuments;
use App\Models\Internship;
use App\Models\InternshipDocument;
use App\Support\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Dokumen KP milik mahasiswa: surat pengantar dan surat balasan perusahaan. */
class DocumentController extends Controller
{
    private function current(Request $request): ?Internship
    {
        return Internship::with(['documents.file', 'company', 'division', 'lecturer'])
            ->where('student_id', $request->user()->id)
            ->whereIn('status', ['diajukan', 'berjalan', 'selesai'])
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 WHEN 'diajukan' THEN 1 ELSE 2 END")
            ->latest('start_date')
            ->first();
    }

    public function index(Request $request): View
    {
        $internship = $this->current($request);

        return view('dokumen.index', [
            'internship' => $internship,
            'canEdit' => $internship && in_array($internship->status, ['diajukan', 'berjalan'], true),
        ]);
    }

    public function requestLetter(Request $request, ManageDocuments $docs): RedirectResponse
    {
        $internship = $this->current($request);
        abort_unless($internship, 404);
        $docs->requestLetter($request->user(), $internship);

        return back()->with('status', 'Permintaan surat pengantar dikirim ke TU Fakultas.');
    }

    public function upload(Request $request, string $type, ManageDocuments $docs): RedirectResponse
    {
        $internship = $this->current($request);
        abort_unless($internship && in_array($internship->status, ['diajukan', 'berjalan'], true), 404);
        abort_unless($type === InternshipDocument::REPLY, 404);

        $request->validate([
            'berkas' => ['required', 'file', 'max:'.ManageDocuments::MAX_KB, 'extensions:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png'],
        ], [
            'berkas.required' => 'Pilih berkas yang akan diunggah.',
            'berkas.max' => 'Ukuran berkas maksimal 5 MB.',
            'berkas.extensions' => 'Berkas harus PDF, JPG, atau PNG.',
            'berkas.mimetypes' => 'Isi berkas harus PDF atau gambar.',
        ]);

        $doc = $docs->upload($request->user(), $internship, $type, $request->file('berkas'));

        return back()->with('status', $doc->label().' terunggah dan menunggu diperiksa TU.');
    }

    /** Berkas unggahan: hanya untuk yang boleh melihat penempatan ini. */
    public function file(Request $request, InternshipDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document->internship);
        $file = $document->file;
        abort_unless($file && Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Surat pengantar siap cetak (simpan sebagai PDF lewat dialog cetak browser). */
    public function letter(Request $request, InternshipDocument $document): Response
    {
        Gate::authorize('view', $document->internship);
        abort_unless($document->type === InternshipDocument::LETTER && $document->status === 'terbit', 404);

        $internship = $document->internship->load(['student.studentProfile.studyProgram', 'company', 'division']);

        return response()->view('dokumen.surat-pengantar', [
            'document' => $document,
            'internship' => $internship,
            'campus' => [
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'city' => Campus::city(),
                'address' => Campus::get('campus.address'),
                'program' => Campus::get('campus.program'),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
