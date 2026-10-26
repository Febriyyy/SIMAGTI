<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\InternshipStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Admin: daftar akun, ubah peran, aktifkan / nonaktifkan akun. */
class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $role = array_key_exists((string) $request->query('peran'), User::ROLE_LABELS) ? $request->query('peran') : null;
        $status = in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null;
        $term = mb_substr(trim((string) $request->query('cari')), 0, 60);

        $users = User::query()
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($status, fn ($q) => $q->where('is_active', $status === 'aktif'))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")->orWhere('nim', 'like', "%{$term}%")))
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'kaprodi' THEN 1 WHEN 'tu' THEN 2 WHEN 'dospem' THEN 3 WHEN 'supervisor' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $counts = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('admin.akun', compact('users', 'counts', 'role', 'status', 'term'));
    }

    public function update(Request $request, User $account): RedirectResponse
    {
        $admin = $request->user();
        $data = $request->validate([
            'role' => ['sometimes', Rule::in(array_keys(User::ROLE_LABELS))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($account->is($admin)) {
            throw ValidationException::withMessages(['role' => 'Anda tidak bisa mengubah peran atau menonaktifkan akun sendiri.']);
        }

        $messages = [];

        if (isset($data['role']) && $data['role'] !== $account->role) {
            if ($reason = $this->roleLock($account)) {
                throw ValidationException::withMessages(['role' => $reason]);
            }
            $old = $account->roleLabel();
            $account->update(['role' => $data['role']]);
            ActivityLog::record('akun.peran', "Mengubah peran {$account->name}: {$old} → {$account->roleLabel()}", $account);
            $messages[] = "Peran {$account->listName()} sekarang {$account->roleLabel()}.";
        }

        if (array_key_exists('is_active', $data) && (bool) $data['is_active'] !== (bool) $account->is_active) {
            if (! $data['is_active'] && $account->role === 'admin' && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['is_active' => 'Minimal harus ada satu admin aktif.']);
            }
            $account->update(['is_active' => (bool) $data['is_active']]);
            if (! $account->is_active) {
                $account->tokens()->delete();
            }
            ActivityLog::record($account->is_active ? 'akun.aktifkan' : 'akun.nonaktifkan', ($account->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." akun {$account->name}", $account);
            $messages[] = 'Akun '.$account->listName().($account->is_active ? ' diaktifkan.' : ' dinonaktifkan.');
        }

        return back()->with('status', $messages ? implode(' ', $messages) : 'Tidak ada perubahan.');
    }

    /** Peran tidak bisa diubah selama akun masih terhubung dengan data KP sesuai perannya. */
    private function roleLock(User $user): ?string
    {
        return match ($user->role) {
            'mahasiswa' => Internship::where('student_id', $user->id)->exists() ? 'Mahasiswa ini sudah punya data KP, perannya tidak bisa diubah. Nonaktifkan saja bila perlu.' : null,
            'supervisor' => InternshipStaff::where('user_id', $user->id)->exists() ? 'Supervisor ini masih terhubung dengan mahasiswa magang. Ganti supervisornya dulu.' : null,
            'dospem', 'kaprodi' => Internship::where('lecturer_id', $user->id)->whereIn('status', Internship::ACTIVE_STATUSES)->exists() ? 'Dosen ini masih membimbing KP yang aktif.' : null,
            'admin' => User::where('role', 'admin')->where('is_active', true)->count() <= 1 ? 'Minimal harus ada satu admin aktif.' : null,
            default => null,
        };
    }
}
