<?php

/*
 * Identitas kampus dan aturan KP bawaan. Nilainya bisa ditimpa dari tabel settings
 * (lihat App\Support\Campus), sehingga mengganti kampus tidak perlu mengubah kode.
 * Data bawaan sengaja fiktif untuk demo dan pameran.
 */
return [
    'campus' => [
        'university' => env('CAMPUS_UNIVERSITY', 'Universitas Satya Mandala'),
        'faculty' => env('CAMPUS_FACULTY', 'Fakultas Ilmu Komputer'),
        'city' => env('CAMPUS_CITY', 'Jakarta'),
        'short' => env('CAMPUS_SHORT', 'USM'),
    ],

    'rules' => [
        // Syarat mengambil KP (Panduan KP: minimal semester V)
        'min_semester' => 5,
        // Magang MBKM Mandiri: semester 5 sampai 7 (Pakta Integritas MB.04)
        'mbkm_min_semester' => 5,
        'mbkm_max_semester' => 7,
        // Logbook MBKM minimal 450 jam (1 SKS = 45 jam)
        'mbkm_min_hours' => 450,
        // Judul laporan maksimal 12 kata (Panduan KP 5.1)
        'title_max_words' => 12,
    ],
];
