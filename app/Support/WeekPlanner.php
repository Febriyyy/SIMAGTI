<?php

namespace App\Support;

use App\Models\ActivityPlan;
use App\Models\Internship;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Kalender satu minggu untuk dashboard mahasiswa (Senin–Minggu, tanpa jam).
 * Task tampil sebagai blok yang membentang dari tanggal mulai sampai tenggat, berwarna sesuai rencananya.
 * Blok disusun ke baris (lajur) supaya tidak saling menimpa.
 */
class WeekPlanner
{
    /**
     * @param  Collection<int, ActivityPlan>  $plans  rencana kegiatan (urutan menentukan warna bawaan)
     * @return array{start: CarbonImmutable, end: CarbonImmutable, offset: int, label: string, days: list<array>, items: list<array>, lanes: int}
     */
    public static function build(Internship $internship, Collection $plans, int $offset = 0, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $start = $today->startOfWeek(CarbonImmutable::MONDAY)->addWeeks($offset);
        $end = $start->addDays(6);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->addDays($i);
            $days[] = [
                'date' => $d,
                'weekday' => $d->locale('id')->translatedFormat('D'),
                'day' => $d->day,
                'today' => $d->isSameDay($today),
                'weekend' => $d->isWeekend(),
            ];
        }

        $colors = $plans->values()->mapWithKeys(fn (ActivityPlan $p, int $k) => [$p->id => $p->colorKey($k)]);
        $titles = $plans->mapWithKeys(fn (ActivityPlan $p) => [$p->id => $p->title]);

        $tasks = $internship->tasks()
            ->with('column')
            ->where(fn ($q) => $q->whereNotNull('start_date')->orWhereNotNull('due_date'))
            ->where(fn ($q) => $q
                ->whereRaw('COALESCE(start_date, due_date) <= ?', [$end->toDateString()])
                ->whereRaw('COALESCE(due_date, start_date) >= ?', [$start->toDateString()]))
            ->get();

        $items = $tasks->map(function ($task) use ($start, $colors, $titles) {
            $from = CarbonImmutable::parse($task->start_date ?? $task->due_date);
            $to = CarbonImmutable::parse($task->due_date ?? $task->start_date);
            if ($to->lt($from)) {
                [$from, $to] = [$to, $from];
            }

            return [
                'task' => $task,
                'col' => max(0, (int) $start->diffInDays($from, false)),
                'span' => 0,
                'to' => min(6, (int) $start->diffInDays($to, false)),
                'color' => $colors[$task->activity_plan_id] ?? 'teal',
                'plan' => $titles[$task->activity_plan_id] ?? null,
                'due' => $task->due_date ? CarbonImmutable::parse($task->due_date)->locale('id')->translatedFormat('D, j M') : null,
                'range' => $from->isSameDay($to)
                    ? $from->locale('id')->translatedFormat('j M')
                    : $from->locale('id')->translatedFormat('j M').' – '.$to->locale('id')->translatedFormat('j M'),
                'done' => $task->isDone(),
                'late' => $task->isOverdue(),
                'continues' => $to->gt($start->addDays(6)),
                'started' => $from->lt($start),
            ];
        })->map(function (array $item) {
            $item['span'] = $item['to'] - $item['col'] + 1;

            return $item;
        })->sortBy([['col', 'asc'], ['span', 'desc']])->values();

        // Susun ke lajur: setiap blok masuk lajur pertama yang masih kosong pada rentang harinya.
        $laneEnds = [];
        $placed = [];
        foreach ($items as $item) {
            $lane = 0;
            while (isset($laneEnds[$lane]) && $laneEnds[$lane] >= $item['col']) {
                $lane++;
            }
            $laneEnds[$lane] = $item['to'];
            $item['lane'] = $lane;
            $placed[] = $item;
        }

        $label = $start->month === $end->month
            ? $start->day.'–'.$end->locale('id')->translatedFormat('j F Y')
            : $start->locale('id')->translatedFormat('j M').' – '.$end->locale('id')->translatedFormat('j M Y');

        return [
            'start' => $start,
            'end' => $end,
            'offset' => $offset,
            'label' => $label,
            'days' => $days,
            'items' => $placed,
            'lanes' => max(1, count($laneEnds)),
        ];
    }
}
