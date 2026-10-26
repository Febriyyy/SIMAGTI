<?php

namespace App\Support;

use App\Models\User;

class Menu
{
    /**
     * Menu sidebar per peran. Hanya halaman yang sudah ada yang ditampilkan.
     *
     * @return array<int, array{label: string, route: string, icon: string, active: string|array<int, string>}>
     */
    public static function for(User $user): array
    {
        $dashboard = ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'active' => 'dashboard'];

        return match ($user->role) {
            'mahasiswa' => [
                $dashboard,
                ['label' => 'Status KP', 'route' => 'kp.status', 'icon' => 'approval', 'active' => 'kp.*'],
                // Rencana kegiatan (Gantt) diisi lebih dulu, baru task dibuat di halaman Kegiatan
                ['label' => 'Timesheet', 'route' => 'timesheet.index', 'icon' => 'calendar', 'active' => 'timesheet.*'],
                ['label' => 'Kegiatan', 'route' => 'kegiatan.index', 'icon' => 'board', 'active' => ['kegiatan.*', 'tugas.show']],
                ['label' => 'Bimbingan', 'route' => 'bimbingan.index', 'icon' => 'chat', 'active' => 'bimbingan.*'],
                ['label' => 'Dokumen', 'route' => 'dokumen.index', 'icon' => 'file', 'active' => 'dokumen.*'],
                ['label' => 'Perubahan', 'route' => 'perubahan.index', 'icon' => 'swap', 'active' => 'perubahan.*'],
            ],
            'supervisor' => [
                $dashboard,
                ['label' => 'Mahasiswa Magang', 'route' => 'pantau.index', 'icon' => 'student', 'active' => ['pantau.*', 'tugas.show']],
                ['label' => 'Aktivitas', 'route' => 'aktivitas.index', 'icon' => 'activity', 'active' => 'aktivitas.*'],
                ['label' => 'Penilaian Akhir', 'route' => 'penilaian.index', 'icon' => 'award', 'active' => 'penilaian.*'],
                ['label' => 'Pengajuan Perubahan', 'route' => 'perubahan.antrean', 'icon' => 'swap', 'active' => 'perubahan.*'],
            ],
            'dospem' => [
                $dashboard,
                ['label' => 'Mahasiswa Bimbingan', 'route' => 'pantau.index', 'icon' => 'student', 'active' => ['pantau.*', 'tugas.show']],
                ['label' => 'Aktivitas', 'route' => 'aktivitas.index', 'icon' => 'activity', 'active' => 'aktivitas.*'],
                ['label' => 'Bimbingan', 'route' => 'bimbingan.antrean', 'icon' => 'chat', 'active' => 'bimbingan.*'],
                ['label' => 'Validasi Penilaian', 'route' => 'validasi.index', 'icon' => 'verify', 'active' => 'validasi.*'],
                ['label' => 'Hasil KP', 'route' => 'hasil.index', 'icon' => 'grade', 'active' => 'hasil.*'],
            ],
            'tu' => [
                $dashboard,
                ['label' => 'Verifikasi KP Mandiri', 'route' => 'tu.verifikasi', 'icon' => 'verify', 'active' => 'tu.verifikasi*'],
                ['label' => 'Aktivasi Supervisor', 'route' => 'tu.aktivasi', 'icon' => 'person', 'active' => 'tu.aktivasi*'],
                ['label' => 'Surat Pengantar', 'route' => 'tu.surat', 'icon' => 'mail', 'active' => 'tu.surat*'],
                ['label' => 'Dokumen KP', 'route' => 'tu.dokumen', 'icon' => 'file', 'active' => 'tu.dokumen*'],
                ['label' => 'Perusahaan', 'route' => 'perusahaan.index', 'icon' => 'building', 'active' => 'perusahaan.*'],
                ['label' => 'Penempatan', 'route' => 'penempatan.index', 'icon' => 'placement', 'active' => 'penempatan.*'],
                ['label' => 'Ganti Supervisor', 'route' => 'perubahan.antrean', 'icon' => 'swap', 'active' => 'perubahan.*'],
            ],
            'kaprodi' => [
                $dashboard,
                ['label' => 'Persetujuan KP', 'route' => 'kaprodi.persetujuan', 'icon' => 'approval', 'active' => 'kaprodi.persetujuan*'],
                ['label' => 'Pengajuan Perubahan', 'route' => 'perubahan.antrean', 'icon' => 'swap', 'active' => 'perubahan.*'],
                ['label' => 'Penempatan', 'route' => 'penempatan.index', 'icon' => 'placement', 'active' => 'penempatan.*'],
                ['label' => 'Pantau Kegiatan', 'route' => 'pantau.index', 'icon' => 'board', 'active' => ['pantau.*', 'tugas.show']],
                ['label' => 'Aktivitas', 'route' => 'aktivitas.index', 'icon' => 'activity', 'active' => 'aktivitas.*'],
                ['label' => 'Mahasiswa', 'route' => 'mahasiswa.index', 'icon' => 'student', 'active' => 'mahasiswa.*'],
                ['label' => 'Perusahaan', 'route' => 'perusahaan.index', 'icon' => 'building', 'active' => 'perusahaan.*'],
                ['label' => 'Bobot Penilaian', 'route' => 'kaprodi.bobot', 'icon' => 'grade', 'active' => 'kaprodi.bobot*'],
            ],
            'admin' => [
                $dashboard,
                ['label' => 'Akun', 'route' => 'admin.akun', 'icon' => 'users', 'active' => 'admin.akun*'],
                ['label' => 'Standar Penilaian', 'route' => 'admin.penilaian', 'icon' => 'award', 'active' => 'admin.penilaian*'],
                ['label' => 'Log Aktivitas', 'route' => 'log.index', 'icon' => 'history', 'active' => 'log.*'],
            ],
            default => [$dashboard],
        };
    }
}
