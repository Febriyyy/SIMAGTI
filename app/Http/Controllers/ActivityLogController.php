<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin: log aktivitas seluruh pengguna, bisa difilter per peran dan dicari. */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $role = array_key_exists((string) $request->query('peran'), User::ROLE_LABELS) ? $request->query('peran') : null;
        $term = mb_substr(trim((string) $request->query('cari')), 0, 60);

        $logs = ActivityLog::with('user')
            ->when($role, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', $role)))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('description', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('log.index', compact('logs', 'role', 'term'));
    }
}
