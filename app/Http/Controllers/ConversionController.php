<?php

namespace App\Http\Controllers;

use App\Actions\Conversions\ManageConversion;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Rekomendasi jadi karyawan: langkah Company Supervisor dan jawaban mahasiswa atas tawaran kerja. */
class ConversionController extends Controller
{
    public function update(Request $request, Internship $internship, ManageConversion $conversion): RedirectResponse
    {
        $data = $request->validate([
            'langkah' => ['required', Rule::in(['rekomendasikan', 'tawarkan', 'diterima', 'ditolak', 'batal'])],
            'posisi' => ['nullable', 'string', 'max:100', Rule::requiredIf($request->input('langkah') === 'tawarkan')],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], ['posisi.required' => 'Isi posisi yang ditawarkan.']);

        $internship->loadMissing(['student', 'company', 'conversion']);
        $user = $request->user();
        $name = $internship->student->listName();

        $message = match ($data['langkah']) {
            'rekomendasikan' => tap("{$name} direkomendasikan untuk direkrut.", fn () => $conversion->recommend($user, $internship, $data['posisi'] ?? null, $data['catatan'] ?? null)),
            'tawarkan' => tap("Tawaran kerja dikirim ke {$name}.", fn () => $conversion->offer($user, $internship, $data['posisi'], $data['catatan'] ?? null)),
            'diterima' => tap("{$name} tercatat menerima tawaran.", fn () => $conversion->record($user, $internship, true)),
            'ditolak' => tap("{$name} tercatat menolak tawaran.", fn () => $conversion->record($user, $internship, false)),
            'batal' => tap('Rekomendasi dibatalkan.', fn () => $conversion->cancel($user, $internship)),
        };

        return back()->with('status', $message);
    }

    /** Mahasiswa menjawab tawaran kerja dari tempat magang. */
    public function respond(Request $request, ManageConversion $conversion): RedirectResponse
    {
        $data = $request->validate(['jawaban' => ['required', Rule::in(['terima', 'tolak'])]]);

        $internship = Internship::with(['student', 'company', 'conversion'])
            ->where('student_id', $request->user()->id)
            ->whereHas('conversion', fn ($q) => $q->where('status', 'ditawari'))
            ->latest('id')->firstOrFail();

        $conversion->respond($request->user(), $internship, $data['jawaban'] === 'terima');

        return back()->with('status', $data['jawaban'] === 'terima' ? 'Selamat! Anda menerima tawaran kerja.' : 'Jawaban Anda tersimpan.');
    }
}
