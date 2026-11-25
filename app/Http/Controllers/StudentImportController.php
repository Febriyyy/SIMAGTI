<?php

namespace App\Http\Controllers;

use App\Actions\Students\CreateStudent;
use App\Actions\Students\ImportStudents;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\SheetReader;
use App\Support\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Impor mahasiswa dari Excel/CSV: unggah, pratinjau (baris valid dan bermasalah), simpan,
 * lalu unduh sekali daftar akun beserta kata sandi sementaranya.
 */
class StudentImportController extends Controller
{
    private const SESSION_CHECK = 'student_import';

    private const SESSION_RESULT = 'student_import_result';

    public function form(Request $request): View
    {
        Gate::authorize('manageStudents', User::class);

        return view('mahasiswa.impor', [
            'check' => $request->session()->get(self::SESSION_CHECK),
            'result' => $request->session()->get(self::SESSION_RESULT),
            'programs' => StudyProgram::when($request->user()->programId(), fn ($q, $id) => $q->whereKey($id))
                ->orderBy('faculty')->orderBy('name')->get(),
        ]);
    }

    /** Template kosong dengan contoh baris, dalam format Excel. */
    public function template(Request $request): BinaryFileResponse
    {
        Gate::authorize('manageStudents', User::class);
        $program = $request->user()->programId() ? StudyProgram::find($request->user()->programId()) : StudyProgram::orderBy('name')->first();

        $path = (new SimpleXlsx)->addSheet('Mahasiswa', [
            ImportStudents::COLUMNS,
            ['Nama Lengkap Mahasiswa', 'nama@mhs.kampus.ac.id', '2310511001', $program?->code ?? 'TIF', (int) date('Y') - 2, 3.5],
        ], [32, 32, 14, 12, 10, 8])->save();

        return response()->download($path, 'template-impor-mahasiswa.xlsx')->deleteFileAfterSend();
    }

    /** Baca berkas dan periksa setiap baris; belum ada yang disimpan. */
    public function check(Request $request, ImportStudents $import): RedirectResponse
    {
        Gate::authorize('manageStudents', User::class);
        $request->validate([
            'berkas' => ['required', 'file', 'max:2048', 'extensions:xlsx,csv,txt'],
        ], [
            'berkas.required' => 'Pilih berkas Excel (.xlsx) atau CSV.',
            'berkas.max' => 'Ukuran berkas maksimal 2 MB.',
            'berkas.extensions' => 'Berkas harus .xlsx atau .csv.',
        ]);

        $file = $request->file('berkas');
        try {
            $rows = SheetReader::read($file->getRealPath(), $file->getClientOriginalExtension(), ImportStudents::MAX_ROWS);
        } catch (RuntimeException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()]);
        }
        if ($rows === []) {
            return back()->withErrors(['berkas' => 'Berkas kosong atau tidak ada baris data.']);
        }
        $missing = array_diff(['nama', 'email', 'nim'], array_keys($rows[0]['data']));
        if ($missing) {
            return back()->withErrors(['berkas' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'. Gunakan template.']);
        }

        $request->session()->forget(self::SESSION_RESULT);
        $request->session()->put(self::SESSION_CHECK, $import->check($request->user(), $rows) + [
            'file' => mb_substr($file->getClientOriginalName(), 0, 120),
        ]);

        return redirect()->route('mahasiswa.impor');
    }

    public function store(Request $request, ImportStudents $import, CreateStudent $create): RedirectResponse
    {
        Gate::authorize('manageStudents', User::class);
        $check = $request->session()->pull(self::SESSION_CHECK);
        if (! $check || $check['valid'] === []) {
            return redirect()->route('mahasiswa.impor')->withErrors(['berkas' => 'Tidak ada baris valid untuk disimpan. Unggah berkas lagi.']);
        }

        $result = $import->import($request->user(), $check['valid'], $create);
        $request->session()->put(self::SESSION_RESULT, $result);

        return redirect()->route('mahasiswa.impor')->with('status', count($result['created']).' mahasiswa ditambahkan.');
    }

    /** Daftar akun dan kata sandi sementara; hanya bisa diunduh sekali. */
    public function credentials(Request $request): BinaryFileResponse|RedirectResponse
    {
        Gate::authorize('manageStudents', User::class);
        $result = $request->session()->pull(self::SESSION_RESULT);
        if (! $result || $result['created'] === []) {
            return redirect()->route('mahasiswa.impor')->withErrors(['berkas' => 'Daftar kata sandi sudah diunduh atau tidak tersedia.']);
        }

        $rows = [['Nama', 'NIM', 'Email', 'Program studi', 'Kata sandi sementara']];
        foreach ($result['created'] as $r) {
            $rows[] = [$r['name'], $r['nim'], $r['email'], $r['program'], $r['password']];
        }
        $path = (new SimpleXlsx)->addSheet('Akun mahasiswa', $rows, [32, 14, 32, 24, 20])->save();

        return response()->download($path, 'akun-mahasiswa-'.now()->format('Ymd-Hi').'.xlsx', [
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend();
    }

    public function cancel(Request $request): RedirectResponse
    {
        Gate::authorize('manageStudents', User::class);
        $request->session()->forget([self::SESSION_CHECK, self::SESSION_RESULT]);

        return redirect()->route('mahasiswa.impor');
    }
}
