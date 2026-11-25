<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\AssessmentRubric;
use App\Models\GradeScale;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\StudyProgram;
use App\Support\AssessmentStandard;
use App\Support\Grading;
use Illuminate\Database\Seeder;

/**
 * Data master yang dibutuhkan sistem agar bisa berjalan (juga di server sungguhan):
 * program studi, periode, skill, skala huruf mutu, dan standar penilaian akhir.
 */
class MasterSeeder extends Seeder
{
    /** Program studi S1 per fakultas: kode => nama */
    public const PROGRAMS = [
        'Fakultas Ekonomi dan Bisnis' => ['MNJ' => 'Manajemen', 'AKT' => 'Akuntansi'],
        'Fakultas Teknik' => ['TS' => 'Teknik Sipil', 'TM' => 'Teknik Mesin', 'TE' => 'Teknik Elektro', 'TIN' => 'Teknik Industri', 'ARS' => 'Arsitektur'],
        'Fakultas Ilmu Komputer' => ['TIF' => 'Teknik Informatika', 'SI' => 'Sistem Informasi'],
        'Fakultas Ilmu Komunikasi' => ['IKOM' => 'Ilmu Komunikasi'],
        'Fakultas Desain dan Seni Kreatif' => ['DP' => 'Desain Produk', 'DI' => 'Desain Interior', 'DKV' => 'Desain Komunikasi Visual'],
        'Fakultas Psikologi' => ['PSI' => 'Psikologi'],
    ];

    public function run(): void
    {
        // Program studi S1 (KP hanya untuk jenjang S1), dikelompokkan per fakultas
        foreach (self::PROGRAMS as $faculty => $programs) {
            foreach ($programs as $code => $name) {
                StudyProgram::firstOrCreate(['code' => $code], ['name' => $name, 'faculty' => $faculty]);
            }
        }

        AcademicPeriod::firstOrCreate(
            ['name' => 'Ganjil 2026/2027'],
            ['start_date' => '2026-07-01', 'end_date' => '2027-01-31', 'is_active' => true]
        );

        $skills = [
            'Bahasa pemrograman' => ['PHP', 'JavaScript', 'Python', 'Java', 'SQL'],
            'Framework' => ['Laravel', 'React', 'Vue.js', 'Flutter'],
            'Data' => ['MySQL', 'Analisis Data', 'Power BI'],
            'Desain' => ['UI/UX'],
            'Alat kerja' => ['Git', 'Docker'],
        ];
        foreach ($skills as $category => $names) {
            foreach ($names as $name) {
                Skill::firstOrCreate(['name' => $name], ['category' => $category]);
            }
        }

        // Skala huruf mutu standar kampus. Admin dapat membuat versi baru kapan saja.
        if (GradeScale::count() === 0) {
            Grading::publishScale(Grading::DEFAULT_SCALE);
        }

        // Standar penilaian akhir kampus: pertanyaan hard skill, soft skill, attitude (diatur admin)
        if (! AssessmentRubric::where('is_active', true)->exists()) {
            AssessmentStandard::createDefaultRubric();
        }

        Setting::firstOrCreate(
            ['key' => 'institution_name'],
            ['value' => ['v' => 'Universitas Contoh'], 'description' => 'Nama kampus yang tampil di dokumen dan sertifikat']
        );
    }
}
