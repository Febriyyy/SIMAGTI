<?php

namespace App\Http\Controllers;

use App\Actions\Students\CreateStudent;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Data mahasiswa, dikelola kaprodi. */
class StudentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manageStudents', User::class);

        $search = trim((string) $request->query('q'));

        $students = User::where('role', 'mahasiswa')
            ->with(['studentProfile.studyProgram', 'internships' => fn ($q) => $q->latest('id')->with('company')])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nim', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(200)
            ->get();

        $programs = StudyProgram::orderBy('name')->get();

        return view('mahasiswa.index', compact('students', 'programs', 'search'));
    }

    public function store(StoreStudentRequest $request, CreateStudent $create): RedirectResponse
    {
        $create->handle($request->user(), $request->validated());

        return redirect()->route('mahasiswa.index')->with('status', 'Mahasiswa ditambahkan.');
    }
}
