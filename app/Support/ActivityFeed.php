<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\WeeklyTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Activity monitor: aktivitas terbaru mahasiswa (dari log aktivitas) untuk Company Supervisor,
 * dosen pembimbing, dan kaprodi. Setiap baris menaut ke halaman pantau yang sesuai.
 */
class ActivityFeed
{
    /** jenis => [label, ikon, awalan aksi di log] */
    public const KINDS = [
        'task' => ['Task', 'board', ['task.', 'bukti.', 'proyek.', 'kolom.']],
        'rencana' => ['Rencana', 'calendar', ['rencana.']],
        'catatan' => ['Catatan', 'clock', ['timesheet.']],
        'goal' => ['Goal', 'flag', ['goal.']],
        'bimbingan' => ['Bimbingan', 'chat', ['bimbingan.']],
        'dokumen' => ['Dokumen', 'file', ['dokumen.', 'perubahan.']],
    ];

    /**
     * @param  Collection<int, Internship>  $internships
     * @return Collection<int, array{log: ActivityLog, internship: Internship, kind: string, label: string, icon: string, url: string}>
     */
    public static function for(Collection $internships, int $limit = 100, ?string $kind = null, ?int $days = null): Collection
    {
        $byStudent = $internships->keyBy('student_id');
        if ($byStudent->isEmpty()) {
            return collect();
        }

        $prefixes = $kind && isset(self::KINDS[$kind]) ? self::KINDS[$kind][2] : array_merge(...array_column(self::KINDS, 2));

        $logs = ActivityLog::with('user')
            ->whereIn('user_id', $byStudent->keys())
            ->where(fn ($q) => collect($prefixes)->each(fn ($p) => $q->orWhere('action', 'like', $p.'%')))
            ->when($days, fn ($q) => $q->where('created_at', '>=', Carbon::today()->subDays($days - 1)))
            ->latest('id')
            ->limit($limit)
            ->get();

        return $logs->map(function (ActivityLog $log) use ($byStudent) {
            $internship = $byStudent->get($log->user_id);
            $kind = self::kindOf($log->action);

            return [
                'log' => $log,
                'internship' => $internship,
                'kind' => $kind,
                'label' => self::KINDS[$kind][0] ?? 'Aktivitas',
                'icon' => self::KINDS[$kind][1] ?? 'history',
                'url' => self::urlFor($log, $internship, $kind),
            ];
        });
    }

    public static function kindOf(string $action): string
    {
        foreach (self::KINDS as $key => [, , $prefixes]) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($action, $prefix)) {
                    return $key;
                }
            }
        }

        return 'task';
    }

    private static function urlFor(ActivityLog $log, Internship $internship, string $kind): string
    {
        if ($log->subject_type === (new WeeklyTask)->getMorphClass() && $log->subject_id
            && WeeklyTask::whereKey($log->subject_id)->exists()) {
            return route('tugas.show', $log->subject_id);
        }

        $tab = match ($kind) {
            'rencana', 'goal' => 'rencana',
            'catatan' => 'timesheet',
            'bimbingan' => 'bimbingan',
            default => null,
        };

        return route('pantau.show', array_filter([$internship, 'tab' => $tab]));
    }

    /** Dikelompokkan per hari untuk tampilan linimasa. @return Collection<string, Collection> */
    public static function byDay(Collection $feed): Collection
    {
        return $feed->groupBy(fn ($item) => $item['log']->created_at->toDateString());
    }
}
