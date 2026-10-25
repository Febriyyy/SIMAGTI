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
        // Magang MBKM Mandiri: semester 5 sampai 7 (Pakta Integritas MB.04)
        'mbkm_min_semester' => 5,
        'mbkm_max_semester' => 7,
        // Logbook MBKM minimal 450 jam (1 SKS = 45 jam)
        'mbkm_min_hours' => 450,
        // Judul laporan maksimal 12 kata (Panduan KP 5.1)
        'title_max_words' => 12,
        // Minimal bimbingan dengan dosen pembimbing sebelum hasil akhir
        'min_guidance' => 5,
        // Surat balasan perusahaan boleh menyusul, paling lambat N hari setelah KP mulai
        'reply_letter_days' => 14,
        // Nilai MBKM: bobot nilai mitra (Company Supervisor) dan dosen pembimbing, dalam persen
        'mbkm_partner_weight' => 60,
        'mbkm_lecturer_weight' => 40,
        // Refleksi bulanan dianggap terlambat setelah N hari dari akhir bulan KP
        'reflection_grace_days' => 5,
    ],
];
