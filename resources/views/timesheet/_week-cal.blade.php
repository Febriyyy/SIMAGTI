{{--
    Kalender Senin–Minggu tanpa jam (dashboard). Tiap task = blok yang membentang dari tanggal mulai sampai tenggat,
    berisi nama rencana, judul task, dan keadaannya (selesai / terlambat / tenggat).
    Variabel: $week (App\Support\WeekPlanner::build)
--}}
<div class="week-cal" style="--lanes: {{ $week['lanes'] }}">
    <div class="week-days" aria-hidden="true">
        @foreach ($week['days'] as $d)
            <span @class(['week-day', 'is-today' => $d['today'], 'is-weekend' => $d['weekend']])>
                <i>{{ $d['weekday'] }}</i><b>{{ $d['day'] }}</b>
            </span>
        @endforeach
    </div>
    <div class="week-grid">
        @foreach ($week['days'] as $i => $d)
            <span @class(['week-col', 'is-today' => $d['today'], 'is-weekend' => $d['weekend']]) style="grid-column: {{ $i + 1 }}" aria-hidden="true"></span>
        @endforeach
        @forelse ($week['items'] as $item)
            @php($task = $item['task'])
            <a href="{{ route('tugas.show', $task) }}"
               @class(['week-task', 'is-done' => $item['done'], 'is-late' => $item['late'], 'from-before' => $item['started'], 'to-after' => $item['continues']])
               style="--c: var(--gc-{{ $item['color'] }}); grid-column: {{ $item['col'] + 1 }} / span {{ $item['span'] }}; grid-row: {{ $item['lane'] + 1 }}"
               title="{{ $task->key() }} · {{ $task->title }} · {{ $item['range'] }}">
                <span class="week-task-plan">{{ $item['plan'] ?? $task->key() }}</span>
                <span class="week-task-title">{{ $task->title }}</span>
                <span class="week-task-meta">
                    @if ($item['done'])
                        <x-ikon name="tick" :size="12" /> Selesai
                    @elseif ($item['late'])
                        Terlambat · tenggat {{ $item['due'] }}
                    @elseif ($item['due'])
                        Tenggat {{ $item['due'] }}
                    @else
                        {{ $item['range'] }}
                    @endif
                </span>
            </a>
        @empty
            <p class="week-empty" style="grid-column: 1 / -1">Tidak ada task {{ $week['offset'] === 0 ? 'minggu ini' : 'di minggu ini' }}.</p>
        @endforelse
    </div>
</div>
