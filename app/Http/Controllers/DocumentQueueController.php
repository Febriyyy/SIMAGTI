<?php

namespace App\Http\Controllers;

use App\Actions\Documents\ManageDocuments;
use App\Models\InternshipDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Antrean dokumen: TU Fakultas menerbitkan surat pengantar, biro memeriksa surat balasan dan pakta integritas. */
class DocumentQueueController extends Controller
{
    public function letters(): View
    {
        $requests = InternshipDocument::with(['internship.student', 'internship.company'])
            ->where('type', InternshipDocument::LETTER)->where('status', 'diminta')->oldest()->get();
        $issued = InternshipDocument::with(['internship.student', 'internship.company'])
            ->where('type', InternshipDocument::LETTER)->where('status', 'terbit')->latest('processed_at')->limit(15)->get();

        return view('tu.surat', compact('requests', 'issued'));
    }

    public function issue(Request $request, InternshipDocument $document, ManageDocuments $docs): RedirectResponse
    {
        $docs->issueLetter($request->user(), $document);

        return back()->with('status', "Surat pengantar {$document->fresh()->number} terbit.");
    }

    public function uploads(): View
    {
        $base = InternshipDocument::with(['internship.student', 'internship.company', 'file'])
            ->whereIn('type', [InternshipDocument::REPLY, InternshipDocument::PACT]);

        $toReview = (clone $base)->where('status', 'diunggah')->oldest('updated_at')->get();
        $missing = (clone $base)->whereIn('status', ['menunggu', 'ditolak'])
            ->whereHas('internship', fn ($q) => $q->where('status', 'berjalan'))->orderBy('due_on')->get();

        return view('biro.dokumen', compact('toReview', 'missing'));
    }

    public function review(Request $request, InternshipDocument $document, ManageDocuments $docs): RedirectResponse
    {
        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['terima', 'tolak'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $docs->review($request->user(), $document, $data['keputusan'] === 'terima', $data['catatan'] ?? null);

        return back()->with('status', $data['keputusan'] === 'terima' ? 'Dokumen diterima.' : 'Mahasiswa diminta mengunggah ulang.');
    }
}
