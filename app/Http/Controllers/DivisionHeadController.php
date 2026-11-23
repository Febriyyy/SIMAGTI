<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CertificateTemplate;
use App\Models\Company;
use App\Models\CompanyDivision;
use App\Models\CompanyStaffProfile;
use App\Models\Internship;
use App\Models\User;
use App\Support\Logos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Data Sertifikat milik Company Supervisor: logo perusahaan, serta nama dan tanda tangan ketua divisi
 * tempat mahasiswanya magang. Semuanya tampil di sertifikat KP.
 */
class DivisionHeadController extends Controller
{
    /** Divisi yang boleh diisi: divisi penempatan mahasiswa yang ia supervisi, dan divisi di profil stafnya. */
    public static function divisionsFor(User $user): Collection
    {
        $ids = Internship::supervisedBy($user)->whereNotNull('division_id')->pluck('division_id')
            ->merge(CompanyStaffProfile::where('user_id', $user->id)->whereNotNull('division_id')->pluck('division_id'))
            ->unique()->values();

        return CompanyDivision::with('company', 'headSignature')->whereIn('id', $ids)->orderBy('company_id')->orderBy('name')->get();
    }

    /** Perusahaan yang logonya boleh ia unggah: perusahaan penempatan mahasiswa yang ia supervisi, dan perusahaan di profil stafnya. */
    public static function companiesFor(User $user): Collection
    {
        $ids = Internship::supervisedBy($user)->whereNotNull('company_id')->pluck('company_id')
            ->merge(CompanyStaffProfile::where('user_id', $user->id)->whereNotNull('company_id')->pluck('company_id'))
            ->unique()->values();

        return Company::whereIn('id', $ids)->orderBy('name')->get();
    }

    public function index(Request $request): View
    {
        $divisions = self::divisionsFor($request->user());
        $companies = self::companiesFor($request->user());
        $signatures = $divisions->mapWithKeys(fn (CompanyDivision $d) => [$d->id => Logos::dataUri($d->headSignature)]);

        // Status penanda tangan kampus (kaprodi), hanya dibaca: nama dan sudah/belum ada tanda tangan.
        // Gambar tanda tangan kaprodi sengaja tidak dikirim ke halaman ini.
        $campus = CertificateTemplate::where('scope', 'kampus')->first();

        return view('divisi.ketua', [
            'companies' => $companies,
            'divisions' => $divisions,
            'signatures' => $signatures,
            'campusSigner' => [
                'title' => $campus?->signer_title ?: 'Ketua Program Studi',
                'name' => $campus?->signer_name,
                'signed' => (bool) $campus?->signature_file_id,
            ],
        ]);
    }

    public function update(Request $request, CompanyDivision $division): RedirectResponse
    {
        abort_unless(self::divisionsFor($request->user())->contains('id', $division->id), 403);

        $data = $request->validateWithBag('divisi'.$division->id, [
            'head_name' => ['required', 'string', 'max:150'],
            'signature' => ['nullable', ...Logos::RULES],
            'remove_signature' => ['nullable', 'boolean'],
        ], [
            'head_name.required' => 'Isi nama ketua divisi.',
            'signature.mimes' => 'Tanda tangan harus PNG, JPG, atau WebP.',
            'signature.max' => 'Ukuran tanda tangan maksimal 1 MB.',
        ]);

        $fields = ['head_name' => trim($data['head_name'])];
        $old = $division->headSignature;
        $replace = $request->hasFile('signature') || ! empty($data['remove_signature']);
        if ($replace) {
            $fields['head_signature_file_id'] = $request->hasFile('signature')
                ? Logos::store($request->file('signature'), $request->user(), 'ttd')->id
                : null;
        }
        $division->update($fields);
        if ($replace) {
            Logos::discard($old);
        }

        ActivityLog::record('divisi.ketua', 'Memperbarui ketua divisi '.$division->name.' ('.$division->company?->name.')', $division);

        return redirect()->to(route('sertifikat.data').'#divisi-'.$division->id)->with('status', 'Ketua divisi '.$division->name.' tersimpan.');
    }

    public function logo(Request $request, Company $company): RedirectResponse
    {
        abort_unless(self::companiesFor($request->user())->contains('id', $company->id), 403);
        $request->validateWithBag('logo'.$company->id, ['logo' => ['required', ...Logos::RULES]], [
            'logo.required' => 'Pilih berkas logo perusahaan.',
            'logo.mimes' => 'Logo harus PNG, JPG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
        ]);

        $old = $company->logo;
        $company->update(['logo_file_id' => Logos::store($request->file('logo'), $request->user(), 'perusahaan')->id]);
        Logos::discard($old);
        ActivityLog::record('perusahaan.logo', 'Mengganti logo '.$company->name, $company);

        return redirect()->to(route('sertifikat.data').'#perusahaan-'.$company->id)->with('status', 'Logo '.$company->name.' tersimpan.');
    }

    public function destroyLogo(Request $request, Company $company): RedirectResponse
    {
        abort_unless(self::companiesFor($request->user())->contains('id', $company->id), 403);
        $old = $company->logo;
        $company->update(['logo_file_id' => null]);
        Logos::discard($old);
        ActivityLog::record('perusahaan.logo', 'Menghapus logo '.$company->name, $company);

        return redirect()->to(route('sertifikat.data').'#perusahaan-'.$company->id)->with('status', 'Logo '.$company->name.' dihapus.');
    }
}
