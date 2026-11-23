<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CertificateTemplate;
use App\Models\CompanyDivision;
use App\Support\Logos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Kaprodi mengisi sendiri nama, jabatan, dan tanda tangannya untuk sertifikat KP
 * (template sertifikat kampus). Berlaku untuk semua sertifikat KP, termasuk yang sudah terbit.
 */
class KaprodiSignatureController extends Controller
{
    public function edit(Request $request): View
    {
        $template = CertificateTemplate::campus($request->user());
        $signature = Logos::dataUri($template->signature);

        return view('kaprodi.tanda-tangan', [
            'template' => $template,
            'signature' => $signature,
            'divisions' => self::companyCompleteness(),
        ]);
    }

    /**
     * Kelengkapan data sertifikat dari Company Supervisor per divisi yang punya mahasiswa magang
     * (berjalan atau selesai): logo perusahaan, nama ketua divisi, tanda tangan ketua divisi.
     * Hanya status, tanpa gambar tanda tangannya. Yang belum lengkap di atas.
     */
    public static function companyCompleteness(): Collection
    {
        return CompanyDivision::query()
            ->whereHas('internships', fn ($q) => $q->whereIn('status', ['berjalan', 'selesai']))
            ->withCount(['internships as students_count' => fn ($q) => $q->whereIn('status', ['berjalan', 'selesai'])])
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

        $template = CertificateTemplate::campus($request->user());
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
