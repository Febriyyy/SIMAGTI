<?php

namespace App\Http\Controllers;

use App\Support\ActiveInternship;
use App\Support\Wrapped;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** KP Wrapped: rangkuman animasi akhir KP untuk mahasiswa, terbuka setelah KP selesai. */
class WrappedController extends Controller
{
    public function show(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['company', 'student', 'assessment']);
        abort_unless($internship && $internship->status === 'selesai', 404, 'KP Wrapped tersedia setelah KP selesai.');

        return view('wrapped.show', ['w' => Wrapped::build($internship)]);
    }
}
