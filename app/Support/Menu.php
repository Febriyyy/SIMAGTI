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
                ['label' => 'Task Mingguan', 'route' => 'tugas.index', 'icon' => 'tasks', 'active' => 'tugas.*'],
                ['label' => 'Pengajuan KP', 'route' => 'pengajuan.index', 'icon' => 'building', 'active' => 'pengajuan.*'],
            ],
            'mentor' => [
                $dashboard,
                ['label' => 'Task Mingguan', 'route' => 'mentor.tugas', 'icon' => 'tasks', 'active' => ['mentor.tugas*', 'tugas.show']],
                ['label' => 'Penilaian Akhir', 'route' => 'mentor.penilaian', 'icon' => 'award', 'active' => 'mentor.penilaian*'],
            ],
            'ketua_divisi' => [
                $dashboard,
                ['label' => 'Validasi Penilaian', 'route' => 'divisi.validasi', 'icon' => 'award', 'active' => 'divisi.validasi*'],
            ],
            'biro' => [
                $dashboard,
                ['label' => 'Verifikasi KP Mandiri', 'route' => 'biro.verifikasi', 'icon' => 'building', 'active' => 'biro.verifikasi*'],
                ['label' => 'Perusahaan', 'route' => 'perusahaan.index', 'icon' => 'building', 'active' => 'perusahaan.*'],
                ['label' => 'Penempatan', 'route' => 'penempatan.index', 'icon' => 'users', 'active' => 'penempatan.*'],
            ],
            'kaprodi' => [
                $dashboard,
                ['label' => 'Persetujuan KP', 'route' => 'kaprodi.persetujuan', 'icon' => 'tasks', 'active' => 'kaprodi.persetujuan*'],
                ['label' => 'Penempatan', 'route' => 'penempatan.index', 'icon' => 'users', 'active' => 'penempatan.*'],
                ['label' => 'Mahasiswa', 'route' => 'mahasiswa.index', 'icon' => 'users', 'active' => 'mahasiswa.*'],
                ['label' => 'Perusahaan', 'route' => 'perusahaan.index', 'icon' => 'building', 'active' => 'perusahaan.*'],
                ['label' => 'Log Aktivitas', 'route' => 'log.index', 'icon' => 'activity', 'active' => 'log.*'],
            ],
            default => [$dashboard],
        };
    }
}
