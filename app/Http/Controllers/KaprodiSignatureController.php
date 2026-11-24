<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CertificateTemplate;
use App\Models\CompanyDivision;
use App\Support\Campus;
use App\Support\Logos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Kaprodi mengisi bagian kampus di sertifikat KP: logo kampus, logo fakultas, serta nama, jabatan,
 * dan tanda tangannya (template sertifikat kampus). Berlaku untuk semua sertifikat KP, termasuk yang sudah terbit.
 */
class KaprodiSignatureController extends Controller
{
    public function edit(Request $request): View
    {
        // Kaprodi berprodi mengisi template prodinya sendiri; tanpa prodi mengisi template umum
        $template = CertificateTemplate::forKaprodi($request->user());
        $signature = Logos::dataUri($template->signature);

        return view('kaprodi.tanda-tangan', [
            'template' => $template,
            'signature' => $signature,
            'logos' => collect(Logos::INSTITUTIONS)->mapWithKeys(fn ($v, $kind) => [$kind => Logos::institution($kind)]),
            'divisions' => self::companyCompleteness($request->user()->programId()),
            'program' => Campus::program($request->user()->programId()),
        ]);
    }

    /** Unggah logo kampus atau logo fakultas (dulu di menu admin Identitas Kampus). */
    public function logo(Request $request, string $kind): RedirectResponse
    {
        abort_unless(isset(Logos::INSTITUTIONS[$kind]), 404);
        $label = Logos::INSTITUTIONS[$kind][1];
        $request->validateWithBag('logo_'.$kind, ['logo' => ['required', ...Logos::RULES]], [
            'logo.required' => 'Pilih berkas '.mb_strtolower($label).'.',
            'logo.mimes' => 'Logo harus PNG, JPG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'logo.image' => 'Berkas harus berupa gambar.',
        ]);

        $file = Logos::store($request->file('logo'), $request->user(), $kind);
        Logos::setInstitution($kind, $file, $request->user());
        ActivityLog::record($kind.'.logo', 'Mengganti '.mb_strtolower($label));

        return back()->with('status', $label.' tersimpan dan langsung dipakai di sertifikat.');
    }

    public function destroyLogo(Request $request, string $kind): RedirectResponse
    {
        abort_unless(isset(Logos::INSTITUTIONS[$kind]), 404);
        Logos::setInstitution($kind, null, $request->user());
        ActivityLog::record($kind.'.logo', 'Menghapus '.mb_strtolower(Logos::INSTITUTIONS[$kind][1]));

        return back()->with('status', Logos::INSTITUTIONS[$kind][1].' dihapus.');
    }

    /**
     * Kelengkapan data sertifikat dari Company Supervisor per divisi yang punya mahasiswa magang
     * (berjalan atau selesai): logo perusahaan, nama ketua divisi, tanda tangan ketua divisi.
     * Hanya status, tanpa gambar tanda tangannya. Yang belum lengkap di atas.
     */
    public static function companyCompleteness(?int $programId = null): Collection
    {
        // Hanya divisi tempat mahasiswa prodi ini magang (semua prodi bila tanpa prodi)
        $active = fn ($q) => $q->whereIn('status', ['berjalan', 'selesai'])->inProgram($programId);

        return CompanyDivision::query()
            ->whereHas('internships', $active)
            ->withCount(['internships as students_count' => $active])
            ->with('company:id,name,logo_file_id')
            ->get()
            ->map(function (CompanyDivision $division) {
                $checks = [
                    'logo' => (bool) $division->company?->logo_file_id,
                    'name' => filled($division->head_name),
                    'signature' => (bool) $division->head_signature_file_id,
                ];

                return ['division' => $division, 'checks' => $checks, 'complete' => ! in_array(false, $checks, true)];
            })
            ->sortBy(fn ($row) => ($row['complete'] ? '1' : '0').'|'.mb_strtolower((string) $row['division']->company?->name).'|'.mb_strtolower($row['division']->name))
            ->values();
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'signer_name' => ['required', 'string', 'max:150'],
            'signer_title' => ['required', 'string', 'max:100'],
            'signature' => ['nullable', ...Logos::RULES],
            'remove_signature' => ['nullable', 'boolean'],
        ], [
            'signer_name.required' => 'Isi nama penanda tangan.',
            'signer_title.required' => 'Isi jabatan penanda tangan.',
            'signature.mimes' => 'Tanda tangan harus PNG, JPG, atau WebP.',
            'signature.max' => 'Ukuran tanda tangan maksimal 1 MB.',
        ]);

        $template = CertificateTemplate::forKaprodi($request->user());
        $fields = ['signer_name' => trim($data['signer_name']), 'signer_title' => trim($data['signer_title'])];
        $old = $template->signature;
        $replace = $request->hasFile('signature') || ! empty($data['remove_signature']);
        if ($replace) {
            $fields['signature_file_id'] = $request->hasFile('signature')
                ? Logos::store($request->file('signature'), $request->user(), 'ttd')->id
                : null;
        }
        $template->update($fields);
        if ($replace) {
            Logos::discard($old);
        }

        ActivityLog::record('sertifikat.ttd', 'Memperbarui penanda tangan sertifikat KP', $template);

        return back()->with('status', 'Tanda tangan sertifikat tersimpan dan langsung dipakai di semua sertifikat KP.');
    }
}
