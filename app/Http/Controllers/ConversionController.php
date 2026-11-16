<?php

namespace App\Http\Controllers;

use App\Actions\Conversions\ManageConversion;
use App\Models\ConversionCandidate;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Rekomendasi jadi karyawan: langkah Company Supervisor, halaman tawaran dan jawaban mahasiswa, surat resmi. */
class ConversionController extends Controller
{
    /** Langkah Company Supervisor: rekomendasikan atau batalkan rekomendasi. */
    public function update(Request $request, Internship $internship, ManageConversion $conversion): RedirectResponse
    {
        $data = $request->validate([
            'langkah' => ['required', Rule::in(['rekomendasikan', 'batal'])],
            'posisi' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $internship->loadMissing(['student', 'company', 'conversion']);
        $name = $internship->student->listName();

        if ($data['langkah'] === 'rekomendasikan') {
            $conversion->recommend($request->user(), $internship, $data['posisi'] ?? null, $data['catatan'] ?? null);

            return back()->with('status', "{$name} direkomendasikan untuk direkrut.");
        }

        $conversion->cancel($request->user(), $internship);

        return back()->with('status', 'Rekomendasi dibatalkan.');
    }

    /** Company Supervisor mengirim tawaran kerja resmi beserta surat PDF. */
    public function offer(Request $request, Internship $internship, ManageConversion $conversion): RedirectResponse
    {
        $data = $request->validateWithBag('offer', [
            'posisi' => ['required', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:100'],
            'lokasi' => ['required', 'string', 'max:150'],
            'mode' => ['required', Rule::in(array_keys(ConversionCandidate::WORK_MODES))],
            'jenis' => ['required', Rule::in(array_keys(ConversionCandidate::EMPLOYMENT_TYPES))],
            'durasi' => ['nullable', 'integer', 'between:1,60', Rule::requiredIf($request->input('jenis') === 'kontrak')],
            'mulai' => ['nullable', 'date', 'after_or_equal:today'],
            'deskripsi' => ['required', 'string', 'max:2000'],
            'kompensasi' => ['nullable', 'string', 'max:150'],
            'hr_nama' => ['required', 'string', 'max:100'],
            'hr_email' => ['required', 'email', 'max:150'],
            'batas' => ['required', 'date', 'after:today', 'before_or_equal:'.now()->addDays(60)->toDateString()],
            'surat' => ['required', 'file', 'max:'.ManageConversion::LETTER_MAX_KB, 'extensions:pdf', 'mimetypes:application/pdf'],
        ], [
            'posisi.required' => 'Isi posisi yang ditawarkan.',
            'lokasi.required' => 'Isi lokasi penempatan.',
            'durasi.required' => 'Isi lama kontrak dalam bulan.',
            'deskripsi.required' => 'Tulis deskripsi pekerjaan, satu baris satu poin.',
            'hr_nama.required' => 'Isi nama kontak HR.',
            'hr_email.required' => 'Isi email kontak HR.',
            'batas.after' => 'Batas jawaban paling cepat besok.',
            'batas.before_or_equal' => 'Batas jawaban paling lama 60 hari dari sekarang.',
            'surat.required' => 'Unggah surat resmi penawaran kerja (PDF).',
            'surat.max' => 'Ukuran surat maksimal 5 MB.',
            'surat.extensions' => 'Surat harus berupa PDF.',
            'surat.mimetypes' => 'Surat harus berupa PDF.',
        ]);

        $internship->loadMissing(['student', 'company', 'conversion']);
        $conversion->offer($request->user(), $internship, $data, $request->file('surat'));

        return redirect()->to(route('pantau.show', $internship).'#rekrutmen')->with('status', 'Tawaran kerja dikirim ke '.rtrim($internship->student->listName(), '.').'.');
    }

    /** Company Supervisor memperpanjang batas jawaban. */
    public function extend(Request $request, Internship $internship, ManageConversion $conversion): RedirectResponse
    {
        $data = $request->validateWithBag('extend', [
            'batas' => ['required', 'date', 'after:today', 'before_or_equal:'.now()->addDays(60)->toDateString()],
        ], ['batas.after' => 'Batas jawaban paling cepat besok.']);

        $internship->loadMissing(['student', 'conversion']);
        $conversion->extend($request->user(), $internship, $data['batas']);

        return redirect()->to(route('pantau.show', $internship).'#rekrutmen')->with('status', 'Batas jawaban diperpanjang.');
    }

    /** Mahasiswa: detail tawaran kerja beserta surat resminya. */
    public function show(Request $request): View
    {
        $internship = $this->studentInternship($request);
        abort_unless($internship, 404);

        return view('rekrutmen.tawaran', ['internship' => $internship, 'offer' => $internship->conversion]);
    }

    /** Mahasiswa menerima, atau menolak dengan alasan. */
    public function respond(Request $request, ManageConversion $conversion): RedirectResponse
    {
        $data = $request->validate([
            'jawaban' => ['required', Rule::in(['terima', 'tolak'])],
            'alasan' => ['nullable', Rule::requiredIf($request->input('jawaban') === 'tolak'), Rule::in(array_keys(ConversionCandidate::DECLINE_REASONS))],
            'pesan' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('jawaban') === 'tolak' && $request->input('alasan') === 'lainnya')],
        ], [
            'alasan.required' => 'Pilih alasan menolak tawaran.',
            'pesan.required' => 'Tuliskan alasan Anda.',
        ]);

        $internship = $this->studentInternship($request);
        abort_unless($internship && $internship->conversion?->status === 'ditawari', 404);

        $conversion->respond($request->user(), $internship, $data['jawaban'] === 'terima', $data['alasan'] ?? null, $data['pesan'] ?? null);

        return redirect()->route('rekrutmen.tawaran')->with('status', $data['jawaban'] === 'terima'
            ? 'Selamat! Anda menerima tawaran kerja. '.$internship->conversion->hr_contact_name.' dari '.$internship->placeName().' akan menghubungi Anda.'
            : 'Jawaban Anda sudah dikirim ke perusahaan. Terima kasih.');
    }

    /** Surat resmi: hanya mahasiswa penerima tawaran dan Company Supervisor penempatan itu. */
    public function letter(Request $request, ConversionCandidate $candidate): StreamedResponse
    {
        $user = $request->user();
        $internship = $candidate->internship;
        $allowed = ($user->role === 'mahasiswa' && (int) $internship->student_id === (int) $user->id && $candidate->status !== 'direkomendasikan')
            || ($user->role === 'supervisor' && $internship->isSupervisedBy($user));
        abort_unless($allowed, 403);

        $file = $candidate->offerLetter;
        abort_unless($file && Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** KP mahasiswa yang punya tawaran kerja (yang sudah ditawarkan; rekomendasi belum terlihat mahasiswa). */
    private function studentInternship(Request $request): ?Internship
    {
        return Internship::with(['student', 'company', 'division', 'supervisor', 'conversion.offerLetter'])
            ->where('student_id', $request->user()->id)
            ->whereHas('conversion', fn ($q) => $q->where('status', '!=', 'direkomendasikan'))
            ->latest('id')->first();
    }
}
