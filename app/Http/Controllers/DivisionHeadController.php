<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
 * Company Supervisor mengisi nama dan tanda tangan ketua divisi tempat mahasiswanya magang.
 * Keduanya tampil di sertifikat KP mahasiswa divisi tersebut.
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

    public function index(Request $request): View
    {
        $divisions = self::divisionsFor($request->user());

        return view('divisi.ketua', [
            'divisions' => $divisions,
            'signatures' => $divisions->mapWithKeys(fn (CompanyDivision $d) => [$d->id => Logos::dataUri($d->headSignature)]),
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

        return redirect()->to(route('divisi.ketua').'#divisi-'.$division->id)->with('status', 'Ketua divisi '.$division->name.' tersimpan.');
    }
}
