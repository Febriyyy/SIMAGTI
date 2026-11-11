{{--
    Timeline rencana kegiatan bergaya lajur melayang (tampilan bawaan; tabel Gantt tetap tersedia sebagai pilihan "Tabel").
    Setiap rencana = lajur berwarna lembut selebar timeline. Label lajur menempel di kiri saat digeser,
    batang rencana melayang dengan cincin progres, task berupa pil di bawahnya (disusun ke baris agar tidak bertumpuk).
    Bulan tampil sebagai pil, garis hari ini menyala. Geser dengan seret mouse; zoom Minggu/Bulan diatur app.js.
    Variabel: $gantt (App\Support\Gantt::build dengan task), $canEdit
--}}
<?php
    // Susun task setiap rencana ke baris, terpisah per skala: Bulan memakai penanda kecil (±4 hari),
    // Minggu memakai pil berlabel (maks. 150px = ±11 hari). 0 baris bila rencana tanpa task.
    $pack = function ($tasks, int $minDays) {
        $ends = [];
        $lines = [];
        foreach ($tasks as $i => $t) {
            $line = 0;
            while (isset($ends[$line]) && $ends[$line] > $t['offset']) {
                $line++;
            }
            $ends[$line] = $t['offset'] + max($t['length'], $minDays);
            $lines[$i] = $line;
        }

        return [$lines, count($ends)];
    };
    $lanes = [];
    foreach ($gantt['rows'] as $r => $row) {
        $tasks = collect($row['tasks'])->filter(fn ($t) => $t['offset'] !== null)->sortBy('offset')->values();
        [$lineMonth, $linesMonth] = $pack($tasks, 5);
        [$lineWeek, $linesWeek] = $pack($tasks, 11);
        $placed = $tasks->map(fn ($t, $i) => $t + ['line_m' => $lineMonth[$i], 'line_w' => $lineWeek[$i]])->all();
        $lanes[$r] = ['tasks' => $placed, 'lines_m' => $linesMonth, 'lines_w' => $linesWeek];
    }
?>
<div class="tl" data-timeline data-zoom="month" style="--days: {{ $gantt['total'] }}">
    <div class="tl-toolbar">
        @includeWhen($viewSwitch ?? false, 'timesheet._view-switch')
        <div class="segmented tl-zoom" role="group" aria-label="Skala timeline">
            <button type="button" class="seg is-active" data-tl-zoom="month" aria-pressed="true">Bulan</button>
            <button type="button" class="seg" data-tl-zoom="week" aria-pressed="false">Minggu</button>
        </div>
        @if ($gantt['today'] !== null)
            <button type="button" class="btn btn-sm" data-tl-today><x-ikon name="calendar" :size="16" /> Hari ini</button>
        @endif
    </div>

    <div class="tl-scroll" data-tl-scroll @if ($gantt['today'] !== null) data-today="{{ $gantt['today'] }}" @endif tabindex="0" aria-label="Timeline rencana kegiatan, geser ke samping">
        <div class="tl-canvas">
            <div class="tl-months" aria-hidden="true">
                @foreach ($gantt['months'] as $m)
                    <span class="tl-month" style="--o: {{ $m['start'] }}; --n: {{ $m['length'] }}"><b>{{ $m['length'] >= 10 ? $m['label'] : $m['short'] }}</b></span>
                @endforeach
            </div>

            @if ($gantt['today'] !== null)
                <div class="tl-today" style="--o: {{ $gantt['today'] }}" aria-hidden="true"><span>Hari ini</span></div>
            @endif

            <div class="tl-lanes">
                @foreach ($gantt['rows'] as $r => $row)
                    @php($plan = $row['plan'])
                    <section @class(['tl-lane', 'is-late' => $row['late'], 'is-'.$row['phase']]) style="--c: var(--gc-{{ $row['color'] }}); --lines-m: {{ $lanes[$r]['lines_m'] }}; --lines-w: {{ $lanes[$r]['lines_w'] }}" aria-label="{{ $plan->title }}">
                        <div class="tl-lane-label">
                            <span class="tl-dot" aria-hidden="true"></span>
                            <span class="tl-lane-title">{{ $plan->title }}</span>
                            <span class="tl-lane-range">{{ $plan->rangeLabel() }}</span>
                        </div>

                        @php($tag = $canEdit ? 'button' : 'div')
                        <{{ $tag }} @if ($canEdit) type="button" data-open="dlg-plan-{{ $plan->id }}" @endif
                            @class(['tl-bar', 'is-empty' => $plan->tasks_count === 0, 'is-done' => $plan->tasks_count > 0 && $row['percent'] === 100])
                            style="--o: {{ $row['offset'] }}; --n: {{ $row['length'] }}; --p: {{ $row['percent'] }}"
                            title="{{ $plan->title }} · {{ $plan->rangeLabel() }}{{ $plan->tasks_count > 0 ? ' · '.$row['percent'].'% task selesai' : '' }}{{ $canEdit ? ' · klik untuk mengubah' : '' }}">
                            <span class="tl-ring" aria-hidden="true"></span>
                            <span class="tl-bar-title">{{ $plan->title }}</span>
                            @if ($plan->tasks_count > 0)
                                <span class="tl-bar-frac">{{ (int) ($plan->completed_tasks_count ?? 0) }}/{{ $plan->tasks_count }}</span>
                            @else
                                <span class="tl-bar-frac">Belum ada task</span>
                            @endif
                        </{{ $tag }}>

                        <div class="tl-tasks">
                            @foreach ($lanes[$r]['tasks'] as $t)
                                @php($task = $t['task'])
                                <a href="{{ route('tugas.show', $task) }}" @class(['tl-task', 'is-done' => $t['done'], 'is-late' => $t['late']])
                                   style="--o: {{ $t['offset'] }}; --n: {{ $t['length'] }}; --line-m: {{ $t['line_m'] }}; --line-w: {{ $t['line_w'] }}"
                                   title="{{ $task->key() }} · {{ $task->title }}{{ $t['late'] ? ' · terlambat' : '' }}">
                                    <span class="tl-task-dot" aria-hidden="true">@if ($t['done'])<x-ikon name="tick" :size="10" />@endif</span>
                                    <span class="tl-task-key">{{ $task->key() }}</span>
                                    <span class="tl-task-title">{{ $task->title }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
