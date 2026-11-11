{{-- Kalender Senin–Minggu tanpa jam (dashboard dan Timesheet). Variabel: $week (App\\Support\\WeekPlanner::build) --}}
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
               title="{{ $task->key() }} · {{ $task->title }}">
                <span class="week-task-key">@if ($item['done'])<x-ikon name="tick" :size="12" />@endif {{ $task->key() }}</span>
                <span class="week-task-title">{{ $task->title }}</span>
            </a>
        @empty
            <p class="week-empty" style="grid-column: 1 / -1">Tidak ada task {{ $week['offset'] === 0 ? 'minggu ini' : 'di minggu ini' }}.</p>
        @endforelse
    </div>
</div>
