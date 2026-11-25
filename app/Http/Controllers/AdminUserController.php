<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Auth\ResetPassword;
use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\InternshipStaff;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Admin: daftar akun, tambah akun staf kampus, ubah peran, aktifkan / nonaktifkan akun, atur ulang kata sandi. */
class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $role = array_key_exists((string) $request->query('peran'), User::ROLE_LABELS) ? $request->query('peran') : null;
        $status = in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null;
        $term = mb_substr(trim((string) $request->query('cari')), 0, 60);
        // Urutan: per peran (bawaan) atau terbaru dibuat, mis. untuk melihat akun hasil aktivasi
        $sort = $request->query('urut') === 'terbaru' ? 'terbaru' : null;

        $users = User::query()
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($status, fn ($q) => $q->where('is_active', $status === 'aktif'))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")->orWhere('nim', 'like', "%{$term}%")))
            ->when($sort === 'terbaru', fn ($q) => $q->latest()->orderByDesc('id'), fn ($q) => $q
                ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'kaprodi' THEN 1 WHEN 'tu' THEN 2 WHEN 'dospem' THEN 3 WHEN 'supervisor' THEN 4 ELSE 5 END")
                ->orderBy('name'))
            ->paginate(25)
            ->withQueryString();

        $counts = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        $programs = StudyProgram::orderBy('name')->get(['id', 'code', 'name']);

        return view('admin.akun', compact('users', 'counts', 'role', 'status', 'term', 'sort', 'programs'));
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

    /** Kata sandi sementara tampil sekali di kartu halaman Akun (lewat flash session), tidak disimpan dalam bentuk asli. */
    /** Admin membuat akun staf kampus; kata sandi sementara tampil sekali di halaman Akun. */
    public function store(Request $request, CreateAccount $action): RedirectResponse
    {
        $data = $request->validateWithBag('account', [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(CreateAccount::ROLES)],
            'study_program_id' => ['nullable', 'integer', Rule::exists('study_programs', 'id')],
            'nidn' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Isi nama lengkap.',
            'email.required' => 'Isi email.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'role.required' => 'Pilih peran.',
            'role.in' => 'Peran ini tidak dibuat dari sini. Mahasiswa ditambah kaprodi, Company Supervisor lewat undangan TU.',
        ]);

        // Satu prodi satu kaprodi: penggantian kaprodi dilakukan di Kampus dan Prodi
        if ($data['role'] === 'kaprodi' && ! empty($data['study_program_id'])) {
            $existing = User::where('role', 'kaprodi')
                ->whereHas('lecturerProfile', fn ($q) => $q->where('study_program_id', $data['study_program_id']))->first();
            if ($existing) {
                throw ValidationException::withMessages(['study_program_id' => "Prodi ini sudah punya kaprodi ({$existing->name}). Ganti lewat Kampus dan Prodi."])
                    ->errorBag('account');
            }
        }
        if (! in_array($data['role'], ['dospem', 'kaprodi'], true)) {
            $data['study_program_id'] = null;
            $data['nidn'] = null;
        }

        [$account, $temporary] = $action->handle($request->user(), $data);

        return redirect()->route('admin.akun', ['urut' => 'terbaru'])
            ->with('status', "Akun {$account->roleLabel()} {$account->listName()} dibuat.")
            ->with('temporary_password', ['name' => $account->listName(), 'email' => $account->email, 'password' => $temporary]);
    }

    public function resetPassword(Request $request, User $account, ResetPassword $action): RedirectResponse
    {
        $temporary = $action->handle($request->user(), $account);

        return back()
            ->with('status', "Kata sandi {$account->listName()} sudah diatur ulang.")
            ->with('temporary_password', ['name' => $account->listName(), 'email' => $account->email, 'password' => $temporary]);
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
