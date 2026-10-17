<?php

namespace App\Http\Controllers;

use App\Actions\Placements\CreatePlacement;
use App\Http\Requests\Placements\StorePlacementRequest;
use App\Models\Company;
use App\Models\CompanyStaffProfile;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Daftar seluruh penempatan KP, dan pembuatan penempatan langsung oleh kaprodi. */
class PlacementController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Internship::class);

        $status = $request->query('status');

        $internships = Internship::with(['student', 'company', 'mentor', 'lecturer'])
            ->withProgress()
            ->when(array_key_exists((string) $status, Internship::STATUSES), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->limit(200)
            ->get();

        $canCreate = $request->user()->can('create', Internship::class);

        return view('penempatan.index', compact('internships', 'status', 'canCreate'));
    }

    public function create(): View
    {
        Gate::authorize('create', Internship::class);

        $activeStudentIds = Internship::whereIn('status', Internship::ACTIVE_STATUSES)->pluck('student_id');

        $students = User::where('role', 'mahasiswa')->where('is_active', true)
            ->whereNotIn('id', $activeStudentIds)
            ->orderBy('name')->get();

        $companies = Company::with('divisions')
            ->whereIn('status', ['mitra', 'mandiri_terverifikasi'])
            ->orderBy('name')->get();

        $lecturers = User::where('role', 'dospem')->where('is_active', true)->orderBy('name')->get();

        $staff = CompanyStaffProfile::with(['user', 'company'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($p) => $p->company->name.' '.$p->user->name);

        return view('penempatan.create', [
            'students' => $students,
            'companies' => $companies,
            'lecturers' => $lecturers,
            'mentors' => $staff->filter(fn ($p) => $p->user->role === 'mentor'),
            'supervisors' => $staff->filter(fn ($p) => $p->user->role === 'ketua_divisi'),
        ]);
    }

    public function store(StorePlacementRequest $request, CreatePlacement $create): RedirectResponse
    {
        $create->handle($request->user(), $request->validated());

        return redirect()->route('penempatan.index')->with('status', 'Penempatan dibuat dan langsung aktif.');
    }
}
