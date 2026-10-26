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
    public function run(): void
    {
        StudyProgram::firstOrCreate(['code' => 'TIF'], ['name' => 'Teknik Informatika', 'faculty' => 'Fakultas Ilmu Komputer']);
        StudyProgram::firstOrCreate(['code' => 'SI'], ['name' => 'Sistem Informasi', 'faculty' => 'Fakultas Ilmu Komputer']);

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
