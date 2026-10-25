<?php

namespace App\Http\Controllers;

use App\Actions\Syllabus\SyllabusWorkflow;
use App\Http\Requests\Syllabus\ReviewSyllabusRequest;
use App\Http\Requests\Syllabus\SaveSyllabusItemRequest;
use App\Models\Internship;
use App\Models\SyllabusItem;
use App\Support\ActiveInternship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Silabus mahasiswa: tujuan hard skill, soft skill, dan attitude yang menjadi dasar penilaian. */
class SyllabusController extends Controller
{
    public function index(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['company', 'supervisor', 'lecturer', 'syllabusItems.tasks']);
        $canEdit = $internship && $request->user()->can('manageSyllabus', $internship);

        return view('silabus.index', compact('internship', 'canEdit'));
    }

    public function store(SaveSyllabusItemRequest $request): RedirectResponse
    {
        $internship = $this->editable($request);
        $data = $request->validated();

        $internship->syllabusItems()->create($data + [
            'position' => (int) $internship->syllabusItems()->where('category', $data['category'])->max('position') + 1,
        ]);

        return back()->with('status', 'Tujuan ditambahkan.');
    }

    public function update(SaveSyllabusItemRequest $request, SyllabusItem $item): RedirectResponse
    {
        $internship = $this->editable($request, $item);
        $item->update($request->validated());

        return back()->with('status', 'Tujuan diperbarui.');
    }

    public function destroy(Request $request, SyllabusItem $item): RedirectResponse
    {
        $this->editable($request, $item);
        $item->delete();

        return back()->with('status', 'Tujuan dihapus.');
    }

    public function submit(Request $request, SyllabusWorkflow $workflow): RedirectResponse
    {
        $internship = $this->editable($request);
        $workflow->submit($request->user(), $internship);

        return back()->with('status', 'Silabus dikirim. Menunggu persetujuan '.($internship->isSeminar() ? 'dosen pembimbing.' : 'Company Supervisor.'));
    }

    public function reopen(Request $request, SyllabusWorkflow $workflow): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);
        Gate::authorize('manageActivities', $internship);

        $workflow->reopen($request->user(), $internship);

        return back()->with('status', 'Silabus bisa diubah lagi. Kirim ulang setelah selesai.');
    }

    public function review(ReviewSyllabusRequest $request, Internship $internship, SyllabusWorkflow $workflow): RedirectResponse
    {
        $approve = $request->input('keputusan') === 'setujui';
        $workflow->review($request->user(), $internship, $approve, $request->input('catatan'));

        return back()->with('status', $approve ? 'Silabus disetujui.' : 'Catatan revisi dikirim ke mahasiswa.');
    }

    /** Penempatan mahasiswa yang silabusnya boleh diubah; item (jika ada) harus milik penempatan itu. */
    private function editable(Request $request, ?SyllabusItem $item = null): Internship
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);
        Gate::authorize('manageSyllabus', $internship);

        if ($item && (int) $item->internship_id !== (int) $internship->id) {
            abort(404);
        }

        return $internship;
    }
}
