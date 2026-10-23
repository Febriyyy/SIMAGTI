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
                // Poin 14: "Pengajuan KP" diganti "Status KP" dan diletakkan tepat di bawah Dashboard
                ['label' => 'Status KP', 'route' => 'kp.status', 'icon' => 'approval', 'active' => 'kp.*'],
                ['label' => 'Silabus', 'route' => 'silabus.index', 'icon' => 'syllabus', 'active' => 'silabus.*'],
                ['label' => 'Kegiatan', 'route' => 'kegiatan.index', 'icon' => 'board', 'active' => ['kegiatan.*', 'tugas.show']],
                ['label' => 'Timesheet', 'route' => 'timesheet.index', 'icon' => 'clock', 'active' => 'timesheet.*'],
            ],
            'supervisor' => [
                $dashboard,
                ['label' => 'Mahasiswa Magang', 'route' => 'pantau.index', 'icon' => 'student', 'active' => ['pantau.*', 'tugas.show']],
                ['label' => 'Penilaian Akhir', 'route' => 'penilaian.index', 'icon' => 'award', 'active' => 'penilaian.*'],
            ],
            'dospem' => [
                $dashboard,
                ['label' => 'Mahasiswa Bimbingan', 'route' => 'pantau.index', 'icon' => 'student', 'active' => ['pantau.*', 'tugas.show']],
                ['label' => 'Penilaian Seminar', 'route' => 'penilaian.index', 'icon' => 'award', 'active' => 'penilaian.*'],
                ['label' => 'Validasi Penilaian', 'route' => 'validasi.index', 'icon' => 'verify', 'active' => 'validasi.*'],
            ],
            'tu' => [
                $dashboard,
                ['label' => 'Aktivasi Supervisor', 'route' => 'tu.aktivasi', 'icon' => 'verify', 'active' => 'tu.aktivasi*'],
                ['label' => 'Penempatan', 'route' => 'penempatan.index', 'icon' => 'placement', 'active' => 'penempatan.*'],
            ],
            'biro' => [
                $dashboard,
                ['label' => 'Verifikasi KP Mandiri', 'route' => 'biro.verifikasi', 'icon' => 'verify', 'active' => 'biro.verifikasi*'],
                ['label' => 'Perusahaan', 'route' => 'perusahaan.index', 'icon' => 'building', 'active' => 'perusahaan.*'],
                ['label' => 'Penempatan', 'route' => 'penempatan.index', 'icon' => 'placement', 'active' => 'penempatan.*'],
            ],
            'kaprodi' => [
                $dashboard,
                ['label' => 'Persetujuan KP', 'route' => 'kaprodi.persetujuan', 'icon' => 'approval', 'active' => 'kaprodi.persetujuan*'],
                ['label' => 'Penempatan', 'route' => 'penempatan.index', 'icon' => 'placement', 'active' => 'penempatan.*'],
                ['label' => 'Pantau Kegiatan', 'route' => 'pantau.index', 'icon' => 'board', 'active' => ['pantau.*', 'tugas.show']],
                ['label' => 'Mahasiswa', 'route' => 'mahasiswa.index', 'icon' => 'student', 'active' => 'mahasiswa.*'],
                ['label' => 'Perusahaan', 'route' => 'perusahaan.index', 'icon' => 'building', 'active' => 'perusahaan.*'],
                ['label' => 'Log Aktivitas', 'route' => 'log.index', 'icon' => 'history', 'active' => 'log.*'],
            ],
            default => [$dashboard],
        };
    }
}
