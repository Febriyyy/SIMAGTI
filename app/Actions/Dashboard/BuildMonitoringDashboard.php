<?php

namespace App\Actions\Dashboard;

use App\Models\Assessment;
use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use App\Support\Grading;
use App\Support\Relative;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data dashboard pemantauan untuk ketua divisi, dosen pembimbing, dan kaprodi.
 * Hanya penempatan yang boleh dilihat pengguna (scope visibleTo) yang dihitung.
 */
class BuildMonitoringDashboard
{
    /** Batas aturan "perlu perhatian" ditulis di satu tempat. */
    public const IDLE_DAYS = 7;          // tidak ada bukti baru selama 7 hari

    public const LOW_PROGRESS = 60;      // progres task di bawah 60%

    public const ENDING_DAYS = 21;       // KP berakhir dalam 3 minggu tetapi belum dinilai

    /** @return array<string, mixed> */
    public function handle(User $user): array
    {
        $internships = Internship::with(['student', 'company', 'mentor', 'assessment'])
            ->withProgress()
            ->visibleTo($user)
            ->whereIn('status', ['diajukan', 'berjalan', 'selesai'])
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 WHEN 'diajukan' THEN 1 ELSE 2 END")
            ->orderBy('end_date')
            ->get();

        $lastActivity = $this->lastEvidenceAt($internships->pluck('id')->all());
        foreach ($internships as $internship) {
            $internship->setAttribute('last_activity_at', $lastActivity[$internship->id] ?? null);
        }

        $ongoing = $internships->where('status', 'berjalan');
        $validated = $internships->map->assessment->filter(fn (?Assessment $a) => $a?->isValidated());

        $data = [
            'internships' => $internships,
            'stats' => [
                'active' => $ongoing->count(),
                'companies' => $ongoing->pluck('company_id')->unique()->count(),
                'average' => $ongoing->isEmpty() ? 0 : (int) round($ongoing->avg(fn (Internship $i) => $i->percent())),
                'finished' => $internships->where('status', 'selesai')->count(),
                'average_score' => $validated->isEmpty() ? null : round((float) $validated->avg('final_score'), 2),
            ],
            'attention' => $this->attention($ongoing),
            'grades' => Grading::distribution($validated->pluck('letter_grade')),
            'assessment' => [
                'none' => $internships->whereIn('status', ['berjalan', 'selesai'])->filter(fn ($i) => $i->assessment === null)->count(),
                'draft' => $internships->filter(fn ($i) => $i->assessment?->status === 'draft')->count(),
                'submitted' => $internships->filter(fn ($i) => $i->assessment?->status === 'submitted')->count(),
                'validated' => $validated->count(),
            ],
        ];

        if ($user->hasRole('ketua_divisi')) {
            $data['waitingValidation'] = $internships->filter(fn ($i) => $i->assessment?->status === 'submitted')->values();
        }

        if ($user->hasRole('kaprodi')) {
            $data['waitingApproval'] = Internship::awaitingKaprodi()->count();
            $data['companies'] = $this->companySummary($internships);
        }

        return $data;
    }

    /**
     * Mahasiswa yang sedang magang dan memenuhi salah satu tanda risiko, beserta alasannya.
     *
     * @param  Collection<int, Internship>  $ongoing
     * @return Collection<int, array{internship: Internship, reasons: list<string>}>
     */
    private function attention(Collection $ongoing): Collection
    {
        return $ongoing
            ->map(function (Internship $i) {
                $reasons = [];
                $last = $i->last_activity_at;

                if ($last === null) {
                    $reasons[] = 'Belum pernah mengirim bukti';
                } elseif (($days = Relative::daysAgo($last)) >= self::IDLE_DAYS) {
                    $reasons[] = "Tidak ada bukti baru {$days} hari";
                }

                if ($i->tasks_count > 0 && $i->percent() < self::LOW_PROGRESS) {
                    $reasons[] = 'Progres task '.$i->percent().'%';
                }

                if ($i->daysLeft() <= self::ENDING_DAYS && ! $i->assessment?->isValidated()) {
                    $reasons[] = 'KP berakhir '.$i->daysLeft().' hari lagi, nilai belum divalidasi';
                }

                return ['internship' => $i, 'reasons' => $reasons];
            })
            ->filter(fn (array $row) => $row['reasons'] !== [])
            ->sortByDesc(fn (array $row) => count($row['reasons']))
            ->values();
    }

    /**
     * Waktu bukti terakhir per penempatan.
     *
     * @param  list<int>  $ids
     * @return array<int, Carbon>
     */
    private function lastEvidenceAt(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('task_evidences')
            ->join('weekly_tasks', 'weekly_tasks.id', '=', 'task_evidences.weekly_task_id')
            ->whereIn('weekly_tasks.internship_id', $ids)
            ->groupBy('weekly_tasks.internship_id')
            ->select('weekly_tasks.internship_id', DB::raw('MAX(task_evidences.created_at) as last_at'))
            ->pluck('last_at', 'internship_id')
            ->map(fn ($value) => Carbon::parse($value))
            ->all();
    }

    /**
     * Ringkasan per perusahaan untuk kaprodi: mahasiswa aktif, progres rata-rata, nilai rata-rata.
     *
     * @param  Collection<int, Internship>  $internships
     * @return Collection<int, array{company: Company, active: int, average: int, score: float|null}>
     */
    private function companySummary(Collection $internships): Collection
    {
        return $internships
            ->whereIn('status', ['berjalan', 'selesai'])
            ->groupBy('company_id')
            ->map(function (Collection $group) {
                $active = $group->where('status', 'berjalan');
                $scores = $group->map->assessment->filter(fn ($a) => $a?->isValidated());

                return [
                    'company' => $group->first()->company,
                    'active' => $active->count(),
                    'average' => $active->isEmpty() ? null : (int) round($active->avg(fn (Internship $i) => $i->percent())),
                    'score' => $scores->isEmpty() ? null : round((float) $scores->avg('final_score'), 2),
                ];
            })
            ->sortByDesc('active')
            ->values();
    }
}
