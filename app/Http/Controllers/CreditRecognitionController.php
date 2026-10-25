<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CreditRecognition;
use App\Support\ActiveInternship;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Rekognisi SKS MBKM: mahasiswa mengusulkan mata kuliah yang disetarakan, kaprodi memutuskan (opsional). */
class CreditRecognitionController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user(), ['creditRecognition']);
        abort_unless($internship && $internship->isMbkm(), 404);

        $current = $internship->creditRecognition;
        if ($current?->status === 'disetujui') {
            throw ValidationException::withMessages(['courses' => 'Rekognisi sudah disetujui kaprodi.']);
        }

        $data = $request->validate([
            'courses' => ['required', 'array', 'min:1', 'max:10'],
            'courses.*.code' => ['nullable', 'string', 'max:20'],
            'courses.*.name' => ['nullable', 'string', 'max:120'],
            'courses.*.credits' => ['nullable', 'integer', 'between:1,6'],
        ]);

        $courses = collect($data['courses'])->filter(fn ($c) => filled($c['name'] ?? null))
            ->map(fn ($c) => ['code' => trim((string) ($c['code'] ?? '')), 'name' => trim($c['name']), 'credits' => (int) ($c['credits'] ?? 0)])
            ->values();
        if ($courses->isEmpty() || $courses->contains(fn ($c) => $c['credits'] < 1)) {
            throw ValidationException::withMessages(['courses' => 'Isi nama mata kuliah dan jumlah SKS (1–6) di setiap baris.']);
        }
        if ($courses->sum('credits') > CreditRecognition::MAX_CREDITS) {
            throw ValidationException::withMessages(['courses' => 'Total SKS maksimal '.CreditRecognition::MAX_CREDITS.'.']);
        }

        $recognition = CreditRecognition::updateOrCreate(
            ['internship_id' => $internship->id],
            ['courses' => $courses->all(), 'status' => 'diajukan', 'note' => null, 'decided_by' => null, 'decided_at' => null]
        );
        ActivityLog::record('rekognisi.ajukan', 'Mengajukan rekognisi '.$recognition->totalCredits().' SKS', $recognition);
        Notify::send(Notify::role('kaprodi'), "Usulan rekognisi SKS {$request->user()->name}", $recognition->totalCredits().' SKS', route('rekognisi.antrean'), 'school');

        return back()->with('status', 'Usulan rekognisi SKS dikirim ke kaprodi.');
    }

    public function queue(): View
    {
        return view('rekognisi.antrean', [
            'pending' => CreditRecognition::with('internship.student', 'internship.company')->where('status', 'diajukan')->oldest('updated_at')->get(),
            'decided' => CreditRecognition::with('internship.student')->where('status', '!=', 'diajukan')->latest('decided_at')->limit(15)->get(),
        ]);
    }

    public function decide(Request $request, CreditRecognition $recognition): RedirectResponse
    {
        abort_unless($recognition->status === 'diajukan', 404);
        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['setujui', 'tolak'])],
            'catatan' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('keputusan') === 'tolak')],
        ], ['catatan.required' => 'Tulis alasan penolakan.']);

        $approve = $data['keputusan'] === 'setujui';
        $recognition->update(['status' => $approve ? 'disetujui' : 'ditolak', 'note' => $data['catatan'] ?? null, 'decided_by' => $request->user()->id, 'decided_at' => now()]);
        $student = $recognition->internship->student;
        ActivityLog::record($approve ? 'rekognisi.setujui' : 'rekognisi.tolak', ($approve ? 'Menyetujui' : 'Menolak')." rekognisi SKS {$student->name}", $recognition);
        Notify::send($student, 'Rekognisi SKS '.($approve ? 'disetujui' : 'ditolak'), $data['catatan'] ?? null, route('kp.status'), 'school', $approve ? 'green' : 'red');

        return back()->with('status', $approve ? 'Rekognisi disetujui.' : 'Rekognisi ditolak.');
    }
}
