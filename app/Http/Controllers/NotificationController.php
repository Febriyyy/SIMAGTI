<?php

namespace App\Http\Controllers;

use App\Support\ActionItems;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman lonceng: hal yang perlu ditindaklanjuti (dihitung dari data) dan kabar terbaru. */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->limit(50)->get();
        $actions = ActionItems::for($user);

        // Membuka halaman ini menandai semua kabar sudah dibaca.
        $user->unreadNotifications()->update(['read_at' => now()]);

        return view('notifikasi.index', compact('actions', 'notifications'));
    }

    /** Membuka satu kabar: tandai dibaca lalu pindah ke halaman terkait (hanya alamat di aplikasi ini). */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? '');
        $internal = $url !== '' && str_starts_with($url, url('/'));

        return redirect()->to($internal ? $url : route('notifikasi.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Semua kabar ditandai sudah dibaca.');
    }
}
