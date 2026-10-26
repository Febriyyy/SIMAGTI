<?php

namespace App\Actions\Companies;

use App\Models\Assessment;
use App\Models\Company;
use App\Support\AssessmentStandard;
use App\Support\Grading;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan satu perusahaan: jumlah mahasiswa berjalan/selesai, progres, durasi,
 * performa (nilai akhir tervalidasi), dan sebaran skill mahasiswa yang sedang magang (dalam persen).
 * Dipakai bersama oleh API dan dashboard web.
 */
class BuildCompanyStats
{
    /** @return array<string, mixed> */
    public function handle(Company $company): array
    {
        $internships = $company->internships()
            ->with(['student:id,name,nim', 'assessment.scores.aspect'])
            ->withProgress()
            ->whereIn('status', ['berjalan', 'selesai'])
            ->get();

        $ongoing = $internships->where('status', 'berjalan')->values();
        $finished = $internships->where('status', 'selesai')->values();
        $validated = $internships->map->assessment->filter(fn ($a) => $a?->isValidated())->values();

        return [
            'company' => ['id' => $company->id, 'name' => $company->name],
            'ongoing_count' => $ongoing->count(),
            'finished_count' => $finished->count(),
            'average_progress_percent' => $ongoing->isEmpty()
                ? 0
                : (int) round($ongoing->avg(fn ($i) => $i->percent())),
            'average_duration_months' => $internships->isEmpty()
                ? 0
                : round($internships->avg(fn ($i) => $i->durationMonths()), 1),
            'students' => $internships->map(fn ($i) => [
                'internship_id' => $i->id,
                'name' => $i->student->name,
                'nim' => $i->student->nim,
                'status' => $i->status,
                'progress_percent' => $i->percent(),
                'duration_months' => $i->durationMonths(),
                'days_left' => $i->status === 'berjalan' ? $i->daysLeft() : 0,
                // Nilai hanya dibuka jika sudah divalidasi ketua divisi.
                'final_score' => $i->assessment?->isValidated() ? (float) $i->assessment->final_score : null,
                'letter_grade' => $i->assessment?->isValidated() ? $i->assessment->letter_grade : null,
            ])->values()->all(),
            'performance' => $this->performance($validated),
            'skills' => $this->skillDistribution($ongoing->pluck('student_id')->all()),
        ];
    }

    /**
     * Performa dari penilaian akhir yang sudah divalidasi: rata-rata nilai, sebaran huruf mutu,
     * rata-rata per kategori standar penilaian (hard skill, soft skill, attitude).
     *
     * @param  Collection<int, Assessment>  $validated
     * @return array<string, mixed>
     */
    private function performance($validated): array
    {
        $scores = $validated->flatMap(fn ($a) => $a->scores)->filter(fn ($s) => $s->aspect && $s->score !== null);
        $aspects = collect(AssessmentStandard::CATEGORIES)
            ->map(fn ($c, $key) => ['key' => $key, 'name' => $c[0], 'items' => $scores->filter(fn ($s) => $s->aspect->category === $key)])
            ->filter(fn ($row) => $row['items']->isNotEmpty())
            ->map(fn ($row) => ['name' => $row['name'], 'average' => round((float) $row['items']->avg('score'), 2)])
            ->values()
            ->all();

        return [
            'assessed_count' => $validated->count(),
            'average_final_score' => $validated->isEmpty() ? null : round((float) $validated->avg('final_score'), 2),
            'grades' => Grading::distribution($validated->pluck('letter_grade')),
            'aspects' => $aspects,
        ];
    }

    /**
     * Persentase mahasiswa (yang sedang magang) yang menguasai tiap skill.
     * Satu mahasiswa boleh punya banyak skill, jadi total persen bisa lebih dari 100.
     *
     * @param  list<int>  $studentIds
     * @return list<array{name: string, students: int, percent: int}>
     */
    private function skillDistribution(array $studentIds): array
    {
        $total = count($studentIds);

        if ($total === 0) {
            return [];
        }

        return DB::table('student_skills')
            ->join('skills', 'skills.id', '=', 'student_skills.skill_id')
            ->whereIn('student_skills.user_id', $studentIds)
            ->groupBy('skills.id', 'skills.name')
            ->orderByRaw('COUNT(DISTINCT student_skills.user_id) DESC')
            ->orderBy('skills.name')
            ->select('skills.name', DB::raw('COUNT(DISTINCT student_skills.user_id) as students'))
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'students' => (int) $row->students,
                'percent' => (int) round($row->students / $total * 100),
            ])
            ->all();
    }
}
