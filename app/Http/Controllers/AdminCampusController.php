<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LecturerProfile;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin: identitas kampus (dipakai di surat, sertifikat, berita acara, rekap) dan daftar program studi
 * beserta kaprodinya. Dengan ini SIMAGTI bisa dipakai kampus mana pun tanpa mengubah .env.
 */
class AdminCampusController extends Controller
{
    /** Kolom identitas kampus: kunci pengaturan => [label, contoh] */
    public const FIELDS = [
        'university' => ['Nama universitas', 'Universitas Satya Mandala'],
        'short' => ['Singkatan', 'USM'],
        'faculty' => ['Fakultas', 'Fakultas Ilmu Komputer'],
        'city' => ['Kota', 'Jakarta'],
        'address' => ['Alamat', 'Jl. Pendidikan Raya No. 45, Jakarta Timur'],
        'letter_code' => ['Kode nomor surat', 'KP/FIK-USM'],
    ];

    public function edit(): View
    {
        $programs = StudyProgram::withCount('students')->orderBy('faculty')->orderBy('name')->get();
        $kaprodis = User::where('role', 'kaprodi')->with('lecturerProfile')->orderBy('name')->get();

        return view('admin.kampus', [
            'campus' => collect(self::FIELDS)->mapWithKeys(fn ($v, $key) => [$key => (string) Campus::get('campus.'.$key)]),
            'programs' => $programs,
            'faculties' => $programs->pluck('faculty')->filter()->unique()->values(),
            'kaprodis' => $kaprodis,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(self::FIELDS)->mapWithKeys(fn ($v, $key) => [$key => ['required', 'string', 'max:200']])->all();
        $data = $request->validateWithBag('kampus', $rules, ['*.required' => 'Kolom ini wajib diisi.']);

        foreach ($data as $key => $value) {
            Setting::put('simagti.campus.'.$key, trim($value), self::FIELDS[$key][0].' (identitas kampus)', $request->user()->id);
        }
        Campus::flush();
        ActivityLog::record('kampus.identitas', 'Memperbarui identitas kampus');

        return back()->with('status', 'Identitas kampus tersimpan dan langsung dipakai di surat dan sertifikat.');
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('prodi', [
            'code' => ['required', 'string', 'max:10', Rule::unique('study_programs', 'code')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:100'],
            'faculty' => ['required', 'string', 'max:120'],
        ], [
            'code.required' => 'Isi kode prodi.',
            'faculty.required' => 'Isi fakultas.',
            'code.unique' => 'Kode prodi sudah dipakai.',
            'name.required' => 'Isi nama prodi.',
        ]);

        $program = StudyProgram::create([
            'code' => mb_strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'faculty' => trim($data['faculty']),
        ]);
        ActivityLog::record('prodi.tambah', 'Menambah program studi '.$program->name, $program);

        return back()->with('status', 'Program studi '.$program->name.' ditambahkan.');
    }

    /** Ganti nama prodi dan tunjuk kaprodinya (kaprodi hanya melihat mahasiswa prodinya). */
    public function updateProgram(Request $request, StudyProgram $program): RedirectResponse
    {
        $data = $request->validateWithBag('prodi'.$program->id, [
            'name' => ['required', 'string', 'max:100'],
            'kaprodi_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'kaprodi')],
        ]);

        $program->update(['name' => trim($data['name'])]);

        // Kaprodi lama prodi ini dilepas, kaprodi terpilih diikat ke prodi ini
        $current = LecturerProfile::where('study_program_id', $program->id)
            ->whereHas('user', fn ($q) => $q->where('role', 'kaprodi'))->get();
        foreach ($current as $profile) {
            if ((int) $profile->user_id !== (int) ($data['kaprodi_id'] ?? 0)) {
                $profile->update(['study_program_id' => null]);
            }
        }
        if (! empty($data['kaprodi_id'])) {
            LecturerProfile::updateOrCreate(['user_id' => $data['kaprodi_id']], ['study_program_id' => $program->id]);
        }

        ActivityLog::record('prodi.ubah', 'Memperbarui program studi '.$program->name, $program);

        return back()->with('status', 'Program studi '.$program->name.' tersimpan.');
    }
}
