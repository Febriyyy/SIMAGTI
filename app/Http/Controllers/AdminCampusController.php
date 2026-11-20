<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Campus;
use App\Support\Logos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin: identitas kampus yang tampil di sertifikat KP, termasuk logo kampus. */
class AdminCampusController extends Controller
{
    public function edit(): View
    {
        return view('admin.kampus', [
            'logo' => Logos::campus(),
            'campus' => [
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'program' => (string) Campus::get('campus.program'),
                'city' => Campus::city(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['logo' => ['required', ...Logos::RULES]], [
            'logo.required' => 'Pilih berkas logo kampus.',
            'logo.mimes' => 'Logo harus PNG, JPG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
            'logo.image' => 'Berkas harus berupa gambar.',
        ]);

        $file = Logos::store($request->file('logo'), $request->user(), 'kampus');
        Logos::setCampus($file, $request->user());
        ActivityLog::record('kampus.logo', 'Mengganti logo kampus');

        return back()->with('status', 'Logo kampus tersimpan dan langsung dipakai di sertifikat.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Logos::setCampus(null, $request->user());
        ActivityLog::record('kampus.logo', 'Menghapus logo kampus');

        return back()->with('status', 'Logo kampus dihapus.');
    }
}
