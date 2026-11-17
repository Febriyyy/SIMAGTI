<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Internship;
use App\Models\JobPosting;
use App\Models\WeeklyTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman depan untuk tamu: memperkenalkan SIMAGTI dengan angka nyata dan lowongan yang sedang dibuka.
 * Pengguna yang sudah masuk langsung diarahkan ke dasbor.
 */
class LandingController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $openPostings = JobPosting::with('company')->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today()));

        return view('landing', [
            'stats' => [
                'interns' => Internship::count(),
                'companies' => Company::count(),
                'tasks' => WeeklyTask::count(),
            ],
            'postings' => (clone $openPostings)->latest('published_at')->take(3)->get(),
            'openCount' => $openPostings->count(),
        ]);
    }
}
