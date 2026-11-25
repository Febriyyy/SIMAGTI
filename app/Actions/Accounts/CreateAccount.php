<?php

namespace App\Actions\Accounts;

use App\Models\ActivityLog;
use App\Models\LecturerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin membuat akun staf kampus (dosen pembimbing, kaprodi, TU Fakultas, admin).
 * Dibuatkan kata sandi sementara yang tampil sekali; pemiliknya wajib menggantinya saat pertama masuk.
 * Dosen dan kaprodi mendapat profil dosen beserta prodinya.
 * Akun mahasiswa dibuat kaprodi, akun Company Supervisor lewat undangan TU Fakultas.
 */
class CreateAccount
{
    /** Peran yang boleh dibuat admin dari menu Akun */
    public const ROLES = ['dospem', 'kaprodi', 'tu', 'admin'];

    /**
     * @param  array{name: string, email: string, role: string, study_program_id?: ?int, nidn?: ?string}  $data  sudah divalidasi
     * @return array{0: User, 1: string} akun baru dan kata sandi sementaranya
     */
    public function handle(User $admin, array $data): array
    {
        $temporary = Str::password(10, symbols: false);

        $user = DB::transaction(function () use ($data, $temporary) {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => mb_strtolower(trim($data['email'])),
                'password' => $temporary,
                'role' => $data['role'],
                'is_active' => true,
                'must_change_password' => true,
            ]);

            if (in_array($data['role'], ['dospem', 'kaprodi'], true)) {
                LecturerProfile::create([
                    'user_id' => $user->id,
                    'study_program_id' => $data['study_program_id'] ?? null,
                    'nidn' => filled($data['nidn'] ?? null) ? trim($data['nidn']) : null,
                ]);
            }

            ActivityLog::record('akun.tambah', "Menambah akun {$user->roleLabel()} {$user->name}", $user);

            return $user;
        });

        return [$user, $temporary];
    }
}
