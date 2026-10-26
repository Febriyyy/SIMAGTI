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
        'address' => env('CAMPUS_ADDRESS', 'Jl. Pendidikan Raya No. 45, Jakarta Timur 13220'),
        'program' => env('CAMPUS_PROGRAM', 'Program Studi Teknik Informatika'),
        // Kode nomor surat pengantar: {nomor}/{kode}/{bulan romawi}/{tahun}
        'letter_code' => env('CAMPUS_LETTER_CODE', 'KP/FIK-USM'),
    ],

    'rules' => [
        // Syarat mengambil KP (Panduan KP: minimal semester V)
        'min_semester' => 5,
        // Judul laporan maksimal 12 kata (Panduan KP 5.1)
        'title_max_words' => 12,
        // Minimal bimbingan dengan dosen pembimbing sebelum hasil akhir
        'min_guidance' => 5,
        // Surat balasan perusahaan boleh menyusul, paling lambat N hari setelah KP mulai
        'reply_letter_days' => 14,
        // Penilaian akhir Company Supervisor dibuka N hari sebelum KP berakhir
        'assessment_open_days' => 14,
    ],

    /*
     * Standar penilaian akhir (bawaan). Admin mengubah pertanyaan, skala nilai, dan huruf mutu;
     * kaprodi mengubah bobot kategori. Nilai yang tersimpan di tabel settings menimpa bawaan ini.
     */
    'assessment' => [
        // Bobot kategori dalam persen (total 100), diatur kaprodi
        'weights' => ['hard' => 35, 'soft' => 35, 'attitude' => 30],
        // Skala nilai yang diklik Company Supervisor untuk setiap pertanyaan, diatur admin
        'scale' => [
            ['label' => 'Sangat baik', 'value' => 95],
            ['label' => 'Baik', 'value' => 85],
            ['label' => 'Cukup', 'value' => 75],
            ['label' => 'Kurang', 'value' => 60],
            ['label' => 'Sangat kurang', 'value' => 45],
        ],
    ],
];
