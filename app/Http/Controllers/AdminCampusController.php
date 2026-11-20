<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Campus;
use App\Support\Logos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin: identitas kampus yang tampil di sertifikat KP, termasuk logo kampus dan logo fakultas. */
class AdminCampusController extends Controller
{
    public function edit(): View
    {
        return view('admin.kampus', [
            'logos' => collect(Logos::INSTITUTIONS)->mapWithKeys(fn ($v, $kind) => [$kind => Logos::institution($kind)]),
            'campus' => [
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'program' => (string) Campus::get('campus.program'),
                'city' => Campus::city(),
            ],
        ]);
    }

    public function update(Request $request, string $kind): RedirectResponse
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

    public function destroy(Request $request, string $kind): RedirectResponse
    {
        abort_unless(isset(Logos::INSTITUTIONS[$kind]), 404);
        Logos::setInstitution($kind, null, $request->user());
        ActivityLog::record($kind.'.logo', 'Menghapus '.mb_strtolower(Logos::INSTITUTIONS[$kind][1]));

        return back()->with('status', Logos::INSTITUTIONS[$kind][1].' dihapus.');
    }
}
