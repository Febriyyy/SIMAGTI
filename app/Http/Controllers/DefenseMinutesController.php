<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Support\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Berita acara sidang KP (siap cetak / simpan PDF dari browser), tersedia setelah kaprodi mengesahkan nilai sidang.
 * Dibuka oleh mahasiswa yang bersangkutan, dosen pembimbingnya, dan kaprodi.
 */
class DefenseMinutesController extends Controller
{
    public function show(Request $request, Internship $internship): View
    {
        $user = $request->user();
        abort_unless(
            (int) $internship->student_id === (int) $user->id
            || ($user->role === 'dospem' && (int) $internship->lecturer_id === (int) $user->id)
            || $user->role === 'kaprodi',
            403,
        );

        $internship->load(['student.studentProfile.studyProgram', 'company', 'lecturer', 'supervisor', 'reportScore', 'assessment.rubric.aspects', 'defenseScore.enteredBy', 'defenseSchedule.examiner']);
        $defense = $internship->defenseScore;
        abort_unless($defense && $defense->lecturer_score !== null, 404, 'Berita acara tersedia setelah nilai sidang disahkan kaprodi.');

        return view('sidang.berita-acara', [
            'internship' => $internship,
            'defense' => $defense,
            'report' => $internship->reportScore,
            'industry' => $internship->assessment?->isValidated() ? $internship->assessment->breakdown() : null,
            'campus' => [
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'program' => (string) Campus::get('campus.program'),
                'city' => Campus::city(),
            ],
        ]);
    }
}
