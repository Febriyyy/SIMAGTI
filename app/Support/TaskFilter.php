<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\WeeklyTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Filter task kegiatan, dipakai board/list mahasiswa, halaman pantau, dan unduhan.
 * Nilai filter hanya id dan kata kunci (bukan data pribadi), jadi aman di query string.
 */
class TaskFilter
{
    /** @var array<string, mixed> */
    public array $values;

    public ?Period $period = null;

    public function __construct(public readonly Internship $internship, array $values)
    {
        $this->values = array_filter([
            'proyek' => isset($values['proyek']) && $values['proyek'] !== '' ? (int) $values['proyek'] : null,
            'periode' => in_array($values['periode'] ?? null, ['ini', 'lalu', 'semua', 'rentang'], true) ? $values['periode'] : null,
            'dari' => self::date($values['dari'] ?? null),
            'sampai' => self::date($values['sampai'] ?? null),
            'status' => in_array($values['status'] ?? null, array_keys(WeeklyTask::STATUSES), true) ? $values['status'] : null,
            'prioritas' => in_array($values['prioritas'] ?? null, array_keys(WeeklyTask::PRIORITIES), true) ? $values['prioritas'] : null,
            'rencana' => isset($values['rencana']) && $values['rencana'] !== '' ? (int) $values['rencana'] : null,
            'goal' => isset($values['goal']) && $values['goal'] !== '' ? (int) $values['goal'] : null,
            'cari' => isset($values['cari']) ? mb_substr(trim((string) $values['cari']), 0, 80) : null,
        ], fn ($v) => $v !== null && $v !== '');

        $this->values['periode'] ??= 'semua';

        $this->period = match ($this->values['periode']) {
            'ini' => Period::for($internship),
            'lalu' => Period::for($internship, null, -1),
            default => null,
        };
    }

    public static function fromRequest(Internship $internship, Request $request, array $defaults = []): self
    {
        return new self($internship, $request->only(['proyek', 'periode', 'dari', 'sampai', 'status', 'prioritas', 'rencana', 'goal', 'cari']) + $defaults);
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    /** Rentang tanggal aktif (periode atau rentang manual); null = semua. @return array{0: Carbon, 1: Carbon}|null */
    public function range(): ?array
    {
        if ($this->period) {
            return [$this->period->start, $this->period->end];
        }

        if ($this->values['periode'] === 'rentang' && (isset($this->values['dari']) || isset($this->values['sampai']))) {
            return [
                Carbon::parse($this->values['dari'] ?? $this->internship->start_date)->startOfDay(),
                Carbon::parse($this->values['sampai'] ?? $this->internship->end_date)->endOfDay(),
            ];
        }

        return null;
    }

    public function apply(Builder $query): Builder
    {
        $v = $this->values;

        $query->when($v['proyek'] ?? null, fn ($q, $id) => $q->where('project_id', $id))
            ->when($v['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($v['prioritas'] ?? null, fn ($q, $p) => $q->where('priority', $p))
            ->when($v['rencana'] ?? null, fn ($q, $id) => $q->where('activity_plan_id', $id))
            ->when($v['goal'] ?? null, fn ($q, $id) => $q->where('goal_id', $id))
            ->when($v['cari'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%')));

        // Satu periode memuat task yang tenggatnya jatuh di periode itu, yang selesai di periode itu,
        // dan task tanpa tenggat yang dibuat di periode itu.
        if ($range = $this->range()) {
            [$from, $to] = $range;
            $query->where(fn ($q) => $q
                ->whereBetween('due_date', [$from->toDateString(), $to->copy()->endOfDay()->toDateTimeString()])
                ->orWhereBetween('completed_at', [$from, $to])
                ->orWhere(fn ($w) => $w->whereNull('due_date')->whereBetween('created_at', [$from, $to])));
        }

        return $query;
    }

    public function isActive(): bool
    {
        return count(array_diff_key($this->values, ['periode' => 1])) > 0 || $this->values['periode'] !== 'semua';
    }

    /** Query string untuk tautan (mis. unduhan) dengan filter yang sama. */
    public function query(array $extra = []): array
    {
        return array_merge($this->values, $extra);
    }
}
