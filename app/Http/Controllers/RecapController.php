<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\ProdiRecap;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Rekap KP tingkat prodi untuk Kaprodi dan TU Fakultas, dengan unduhan Excel. */
class RecapController extends Controller
{
    public function index(Request $request): View
    {
        [$filter, $search] = $this->filters($request);
        $internships = ProdiRecap::internships($filter, $search);

        return view('rekap.index', [
            'internships' => $internships,
            'summary' => ProdiRecap::summary($internships),
            'filter' => $filter,
            'search' => $search,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        [$filter, $search] = $this->filters($request);
        $internships = ProdiRecap::internships($filter, $search);
        ActivityLog::record('rekap.unduh', 'Mengunduh rekap KP ('.ProdiRecap::FILTERS[$filter][0].', '.$internships->count().' mahasiswa)');

        return response()->download(ProdiRecap::xlsx($internships, ProdiRecap::FILTERS[$filter][0]), 'rekap-kp-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /** @return array{0: string, 1: ?string} */
    private function filters(Request $request): array
    {
        $filter = array_key_exists((string) $request->query('status'), ProdiRecap::FILTERS) ? (string) $request->query('status') : 'aktif';
        $search = trim((string) $request->query('q')) ?: null;

        return [$filter, $search !== null ? mb_substr($search, 0, 60) : null];
    }
}
