<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CertificateTemplate;
use App\Support\Logos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('kaprodi.tanda-tangan', [
            'template' => $template,
            'signature' => Logos::dataUri($template->signature),
        ]);
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
