<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Support\ActivityFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Activity monitor: linimasa aktivitas mahasiswa yang dipantau (supervisor, dosen pembimbing, kaprodi). */
class ActivityMonitorController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $internships = Internship::visibleTo($user)
            ->with(['student', 'company'])
            ->whereIn('status', ['berjalan', 'selesai'])
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 ELSE 1 END")
            ->latest('start_date')
            ->get()
            ->unique('student_id')
            ->values();

        $kind = array_key_exists((string) $request->query('jenis'), ActivityFeed::KINDS) ? $request->query('jenis') : null;
        $days = in_array($request->integer('hari'), [1, 7, 30], true) ? $request->integer('hari') : 7;
        $studentId = $request->integer('mahasiswa') ?: null;

        $scope = $studentId ? $internships->where('student_id', $studentId)->values() : $internships;
        $feed = ActivityFeed::for($scope, 200, $kind, $days);

        // Mahasiswa yang tidak mencatat apa pun di rentang ini (perlu diperhatikan)
        $active = $feed->pluck('internship.student_id')->unique();
        $quiet = $internships->where('status', 'berjalan')->reject(fn ($i) => $active->contains($i->student_id))->values();

        return view('aktivitas.index', [
            'internships' => $internships,
            'feed' => $feed,
            'days' => ActivityFeed::byDay($feed),
            'quiet' => $studentId || $kind ? collect() : $quiet,
            'kind' => $kind,
            'range' => $days,
            'studentId' => $studentId,
        ]);
    }
}
