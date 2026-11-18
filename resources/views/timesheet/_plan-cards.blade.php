{{--
    Kartu per rencana kegiatan (dashboard mahasiswa dan timesheet): ikon keadaan, label, judul, tanggal, progres task.
    Progres ditampilkan sebagai segmen per task (sampai 12 task), lebih dari itu sebagai batang.
    Seluruh kartu bisa diklik dan membuka Kegiatan yang tersaring ke rencana itu (?rencana=).
    Variabel: $plans (withCount tasks, completed_tasks_count)
--}}
<ul class="plan-cards">
    @foreach ($plans as $k => $plan)
        @php
            $pct = $plan->tasks_count > 0 ? (int) round($plan->completed_tasks_count / $plan->tasks_count * 100) : 0;
            $phase = $plan->phase();
            $state = match (true) {
                $plan->tasks_count > 0 && $pct === 100 => ['done', 'Selesai', 'check'],
                $phase === 'past' && $plan->tasks_count > 0 => ['late', 'Terlambat', 'alert'],
                $phase === 'past' => ['past', 'Sudah lewat', 'history'],
                $phase === 'active' => ['active', 'Berjalan', 'timer'],
                default => ['upcoming', 'Akan datang', 'calendar'],
            };
        @endphp
        <li class="plan-card" data-state="{{ $state[0] }}" style="--c: var(--gc-{{ $plan->colorKey($k) }})">
            <span class="plan-card-top">
                <span class="plan-card-mark" aria-hidden="true"><x-ikon :name="$state[2]" :size="18" /></span>
                <span class="plan-card-state">{{ $state[1] }}</span>
                <span class="plan-card-go" aria-hidden="true"><x-ikon name="arrow-up-right" :size="16" /></span>
            </span>
            <a href="{{ route('kegiatan.index', ['rencana' => $plan->id]) }}" class="plan-card-title plan-card-open" title="Lihat task {{ $plan->title }}">{{ $plan->title }}</a>
            <span class="plan-card-sub"><x-ikon name="calendar" :size="14" /> {{ $plan->rangeLabel() }}<span class="plan-card-days">{{ $plan->days() }} hari</span></span>
            <span class="plan-card-foot">
                @if ($plan->tasks_count > 0)
                    @if ($plan->tasks_count <= 12)
                        <span class="plan-card-segs" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Task selesai {{ $plan->title }}">
                            @for ($n = 0; $n < $plan->tasks_count; $n++)<i @class(['is-on' => $n < $plan->completed_tasks_count])></i>@endfor
                        </span>
                    @else
                        <span class="plan-card-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Task selesai {{ $plan->title }}"><span style="width: {{ $pct }}%"></span></span>
                    @endif
                    <b>{{ $plan->completed_tasks_count }}/{{ $plan->tasks_count }} task</b>
                @else
                    <small class="plan-card-empty">Belum ada task</small>
                @endif
            </span>
        </li>
    @endforeach
</ul>
