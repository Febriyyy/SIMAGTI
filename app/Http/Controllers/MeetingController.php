<?php

namespace App\Http\Controllers;

use App\Actions\Meetings\ScheduleMeeting;
use App\Http\Requests\Meetings\StoreMeetingRequest;
use Illuminate\Http\RedirectResponse;

class MeetingController extends Controller
{
    /** Company Supervisor (seminar: dosen pembimbing) mengatur meeting minggu ini, satu link boleh dipakai beberapa mahasiswa. */
    public function store(StoreMeetingRequest $request, ScheduleMeeting $schedule): RedirectResponse
    {
        $data = $request->validated();

        $schedule->handle($request->user(), $request->internships(), $data['day'], $data['time'], $data['url']);

        return redirect()->route('dashboard')->with('status', 'Jadwal meeting tersimpan.');
    }
}
