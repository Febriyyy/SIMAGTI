<?php

namespace App\Http\Controllers;

use App\Actions\Changes\HandleChangeRequest;
use App\Models\ChangeRequest;
use App\Models\Internship;
use App\Support\ActiveInternship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pengajuan perubahan: perpanjangan, pindah tempat, pengunduran diri, ganti Company Supervisor. */
class ChangeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['supervisor']);
        $history = $internship
            ? ChangeRequest::with('decider')->whereHas('internship', fn ($q) => $q->where('student_id', $request->user()->id))->latest('id')->get()
            : collect();

        return view('perubahan.index', [
            'internship' => $internship,
            'history' => $history,
            'canRequest' => $internship && $internship->status === 'berjalan' && ! $history->contains(fn ($c) => $c->isPending()),
        ]);
    }

    public function store(Request $request, HandleChangeRequest $handler): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship && $internship->status === 'berjalan', 404);

        $handler->create($request->user(), $internship, $request->input('type'), $this->validated($request, $internship));

        return back()->with('status', 'Pengajuan perubahan dikirim.');
    }

    /** Company Supervisor meminta ganti supervisor (resign atau dipindah) untuk mahasiswanya. */
    public function storeBySupervisor(Request $request, Internship $internship, HandleChangeRequest $handler): RedirectResponse
    {
        abort_unless($internship->isSupervisedBy($request->user()), 403);
        $request->merge(['type' => 'replace_supervisor']);
        $handler->create($request->user(), $internship, 'replace_supervisor', $this->validated($request, $internship));

        return back()->with('status', 'Permintaan ganti supervisor dikirim ke TU Fakultas.');
    }

    /** Antrean sesuai peran: supervisor (mahasiswanya), kaprodi, TU Fakultas. */
    public function queue(Request $request): View
    {
        $user = $request->user();
        $step = ['supervisor' => 'supervisor', 'kaprodi' => 'kaprodi', 'tu' => 'tu'][$user->role] ?? abort(403);

        $query = ChangeRequest::with(['internship.student', 'internship.company', 'requester'])->where('status', 'pending')->where('step', $step);
        if ($step === 'supervisor') {
            $query->whereHas('internship', fn ($q) => $q->supervisedBy($user));
        }

        $recent = ChangeRequest::with(['internship.student', 'decider'])->where('status', '!=', 'pending')
            ->when($step === 'supervisor', fn ($q) => $q->whereHas('internship', fn ($i) => $i->supervisedBy($user)))
            ->when($step === 'tu', fn ($q) => $q->where('type', 'replace_supervisor'))
            ->latest('decided_at')->limit(10)->get();

        return view('perubahan.antrean', ['pending' => $query->oldest()->get(), 'recent' => $recent, 'step' => $step]);
    }

    public function decide(Request $request, ChangeRequest $change, HandleChangeRequest $handler): RedirectResponse
    {
        $user = $request->user();
        $allowed = match ($change->step) {
            'supervisor' => $change->internship->isSupervisedBy($user),
            'kaprodi' => $user->role === 'kaprodi',
            'tu' => $user->role === 'tu',
            default => false,
        };
        abort_unless($allowed && $change->isPending(), 403);

        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['setujui', 'tolak'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $handler->decide($user, $change, $data['keputusan'] === 'setujui', $data['catatan'] ?? null);

        return back()->with('status', 'Keputusan tersimpan.');
    }

    private function validated(Request $request, Internship $internship): array
    {
        $type = $request->input('type');

        return $request->validate([
            'type' => ['required', Rule::in(array_keys(ChangeRequest::TYPES))],
            'reason' => ['required', 'string', 'max:1000'],
            'new_end_date' => [Rule::requiredIf($type === 'extend'), 'nullable', 'date', 'after:'.$internship->end_date->toDateString(),
                'before_or_equal:'.$internship->end_date->copy()->addMonths(6)->toDateString()],
            'supervisor_name' => [Rule::requiredIf($type === 'replace_supervisor'), 'nullable', 'string', 'max:150'],
            'supervisor_title' => ['nullable', 'string', 'max:100'],
            'supervisor_email' => [Rule::requiredIf($type === 'replace_supervisor'), 'nullable', 'email', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
        ], [
            'new_end_date.after' => 'Tanggal selesai baru harus setelah tanggal selesai sekarang.',
            'new_end_date.before_or_equal' => 'Perpanjangan maksimal 6 bulan.',
        ], ['reason' => 'alasan', 'new_end_date' => 'tanggal selesai baru', 'supervisor_name' => 'nama supervisor baru', 'supervisor_email' => 'email supervisor baru']);
    }
}
