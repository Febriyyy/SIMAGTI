<?php

namespace App\Http\Controllers;

use App\Support\ActiveInternship;
use App\Support\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Draf laporan KP otomatis untuk mahasiswa: kerangka laporan (A4 tegak) yang diisi dari data KP
 * (tempat, rencana kegiatan, task, goal, catatan per minggu, bimbingan). Siap cetak / Simpan PDF,
 * dan bisa disalin sebagai teks ke Word. Bagian yang harus ditulis sendiri ditandai kotak isian.
 */
class ReportDraftController extends Controller
{
    public function show(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['company', 'division', 'lecturer', 'supervisor', 'student']);
        abort_unless($internship, 404, 'Belum ada KP berjalan.');

        $start = $internship->start_date->copy()->startOfDay();

        $plans = $internship->activityPlans()
            ->with(['tasks' => fn ($q) => $q->orderBy('number'), 'goal'])
            ->orderBy('start_date')
            ->get();

        $weeks = $internship->timeEntries()->with('task')->orderBy('work_date')->orderBy('id')->get()
            ->groupBy(fn ($e) => intdiv((int) $start->diffInDays(Carbon::parse($e->work_date)->startOfDay()), 7) + 1)
            ->map(function ($entries, $week) use ($start) {
                $from = $start->copy()->addWeeks($week - 1);

                return ['number' => $week, 'from' => $from, 'to' => $from->copy()->addDays(6), 'entries' => $entries];
            })
            ->values();

        $tasks = $internship->tasks()->get();

        return view('laporan.draf', [
            'internship' => $internship,
            'student' => $internship->student,
            'academic' => $internship->student->academicIdentity(),
            'plans' => $plans,
            'weeks' => $weeks,
            'goals' => $internship->goals()->orderBy('position')->get(),
            'sessions' => $internship->guidanceSessions()->where('status', 'confirmed')->orderBy('held_on')->get(),
            'stats' => [
                'tasks' => $tasks->count(),
                'done' => $tasks->where('status', 'completed')->count(),
                'days' => $internship->timeEntries()->distinct()->count('work_date'),
                'plans' => $plans->count(),
            ],
            'campus' => [
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'program' => Campus::program($internship->programId()),
                'city' => Campus::city(),
            ],
        ]);
    }
}
