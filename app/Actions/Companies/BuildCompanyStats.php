<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan satu perusahaan: jumlah mahasiswa berjalan/selesai, progres, durasi,
 * dan sebaran skill mahasiswa yang sedang magang (dalam persen).
 * Dipakai bersama oleh API dan dashboard web.
 */
class BuildCompanyStats
{
    /** @return array<string, mixed> */
    public function handle(Company $company): array
    {
        $internships = $company->internships()
            ->with('student:id,name,nim')
            ->withProgress()
            ->whereIn('status', ['berjalan', 'selesai'])
            ->get();

        $ongoing = $internships->where('status', 'berjalan')->values();
        $finished = $internships->where('status', 'selesai')->values();

        return [
            'company' => ['id' => $company->id, 'name' => $company->name],
            'ongoing_count' => $ongoing->count(),
            'finished_count' => $finished->count(),
            'average_progress_percent' => $ongoing->isEmpty()
                ? 0
                : (int) round($ongoing->avg(fn ($i) => $i->percent())),
            'students' => $internships->map(fn ($i) => [
                'internship_id' => $i->id,
                'name' => $i->student->name,
                'nim' => $i->student->nim,
                'status' => $i->status,
                'progress_percent' => $i->percent(),
                'duration_months' => $i->durationMonths(),
                'days_left' => $i->status === 'berjalan' ? $i->daysLeft() : 0,
            ])->values()->all(),
            'skills' => $this->skillDistribution($ongoing->pluck('student_id')->all()),
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
