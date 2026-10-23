<?php

namespace App\Http\Controllers;

use App\Actions\Supervisors\ActivateCompanySupervisor;
use App\Http\Requests\Supervisors\ActivationRequest;
use App\Models\Internship;
use App\Models\InternshipStaff;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** TU Fakultas: antrean aktivasi Company Supervisor dari pengajuan mahasiswa yang sudah disetujui. */
class SupervisorActivationController extends Controller
{
    public function index(): View
    {
        $pending = Internship::awaitingSupervisor()
            ->with(['student.studentProfile.studyProgram', 'company', 'division'])
            ->orderBy('start_date')
            ->get();

        $emails = $pending->pluck('supervisor_email')->unique();
        $users = User::whereIn('email', $emails)->get()->keyBy('email');
        $invitations = Invitation::whereIn('email', $emails)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->get();

        $groups = $pending
            ->groupBy(fn (Internship $i) => $i->company_id.'|'.$i->supervisor_email)
            ->map(function ($items) use ($users, $invitations) {
                $first = $items->first();

                return [
                    'company' => $first->company,
                    'email' => $first->supervisor_email,
                    'name' => $first->supervisor_name,
                    'title' => $first->supervisor_title,
                    'phone' => $first->supervisor_phone,
                    'internships' => $items,
                    'user' => $users->get($first->supervisor_email),
                    'invitation' => $invitations->first(fn ($inv) => $inv->email === $first->supervisor_email && $inv->company_id === $first->company_id),
                ];
            })
            ->values();

        $recent = InternshipStaff::with(['user', 'internship.student', 'internship.company'])
            ->where('role', InternshipStaff::ROLE_SUPERVISOR)
            ->latest('id')
            ->limit(8)
            ->get();

        return view('tu.aktivasi', compact('groups', 'recent'));
    }

    public function invite(ActivationRequest $request, ActivateCompanySupervisor $activate): RedirectResponse
    {
        $invitation = $activate->invite($request->user(), $request->integer('company_id'), (string) $request->input('email'));

        return back()
            ->with('status', "Undangan untuk {$invitation->email} diterbitkan. Salin link di bawah dan kirim ke supervisor.")
            ->with('invitation_link', route('undangan.show', $invitation->token));
    }

    public function link(ActivationRequest $request, ActivateCompanySupervisor $activate): RedirectResponse
    {
        $user = $activate->link($request->user(), $request->integer('company_id'), (string) $request->input('email'));

        return back()->with('status', "{$user->name} ditautkan sebagai Company Supervisor.");
    }

    public function updateEmail(ActivationRequest $request, ActivateCompanySupervisor $activate): RedirectResponse
    {
        $count = $activate->changeEmail($request->user(), $request->integer('company_id'), (string) $request->input('email'), (string) $request->input('new_email'));

        return back()->with('status', "Email supervisor diperbarui untuk {$count} mahasiswa.");
    }
}
