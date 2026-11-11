{{--
    Rencana kegiatan di dashboard mahasiswa (acuan: kalender mingguan berwarna).
    Atas: kartu berwarna per rencana dengan progres task. Bawah: kalender Senin–Minggu (tanpa jam),
    task sebagai blok berwarna sesuai rencana, membentang dari tanggal mulai sampai tenggat; hari ini disorot.
    Variabel: $plans, $week (App\Support\WeekPlanner::build)
--}}
<section class="card week-card" aria-labelledby="week-title">
    <div class="card-head">
        <div>
            <h2 class="card-title" id="week-title">Rencana kegiatan</h2>
        </div>
        <div class="week-nav">
            <a href="{{ route('dashboard', ['minggu' => $week['offset'] - 1]) }}#week-title" class="btn btn-sm btn-icon" aria-label="Minggu sebelumnya"><x-ikon name="back" :size="16" /></a>
            <span class="week-label">{{ $week['offset'] === 0 ? 'Minggu ini' : $week['label'] }}</span>
            <a href="{{ route('dashboard', ['minggu' => $week['offset'] + 1]) }}#week-title" class="btn btn-sm btn-icon" aria-label="Minggu berikutnya"><x-ikon name="next" :size="16" /></a>
            <a href="{{ route('timesheet.index') }}" class="btn btn-sm">Timesheet</a>
        </div>
    </div>

    {{-- Kartu rencana berwarna --}}
    <ul class="plan-cards">
        @foreach ($plans as $k => $plan)
            @php($pct = $plan->tasks_count > 0 ? (int) round($plan->completed_tasks_count / $plan->tasks_count * 100) : 0)
            <li class="plan-card" style="--c: var(--gc-{{ $plan->colorKey($k) }})">
                <span class="plan-card-title" title="{{ $plan->title }}">{{ $plan->title }}</span>
                <span class="plan-card-sub">{{ $plan->rangeLabel() }}</span>
                <span class="plan-card-foot">
                    <span class="plan-card-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Task selesai {{ $plan->title }}"><span style="width: {{ $pct }}%"></span></span>
                    <b>{{ $plan->tasks_count > 0 ? $plan->completed_tasks_count.'/'.$plan->tasks_count : '–' }}</b>
                </span>
            </li>
        @endforeach
    </ul>

    {{-- Kalender minggu --}}
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
</section>
