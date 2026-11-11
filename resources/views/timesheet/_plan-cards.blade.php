{{--
    Kartu per rencana kegiatan (dashboard). Kartu terang dengan titik warna rencana, status yang jelas
    (Berjalan / Selesai / Akan datang / Terlambat), tanggal, dan progres task. Rencana yang sedang berjalan disorot.
    Variabel: $plans (withCount tasks, completed_tasks_count)
--}}
<ul class="plan-cards">
    @foreach ($plans as $k => $plan)
        @php
            $pct = $plan->tasks_count > 0 ? (int) round($plan->completed_tasks_count / $plan->tasks_count * 100) : 0;
            $phase = $plan->phase();
            $state = match (true) {
                $plan->tasks_count > 0 && $pct === 100 => ['done', 'Selesai'],
                $phase === 'past' && $plan->tasks_count > 0 => ['late', 'Terlambat'],
                $phase === 'past' => ['past', 'Sudah lewat'],
                $phase === 'active' => ['active', 'Berjalan'],
                default => ['upcoming', 'Akan datang'],
            };
        @endphp
        <li class="plan-card" data-state="{{ $state[0] }}" style="--c: var(--gc-{{ $plan->colorKey($k) }})">
            <span class="plan-card-top">
                <span class="plan-card-dot" aria-hidden="true"></span>
                <span class="plan-card-state">{{ $state[1] }}</span>
            </span>
            <span class="plan-card-title" title="{{ $plan->title }}">{{ $plan->title }}</span>
            <span class="plan-card-sub">{{ $plan->rangeLabel() }}</span>
            <span class="plan-card-foot">
                @if ($plan->tasks_count > 0)
                    <span class="plan-card-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Task selesai {{ $plan->title }}"><span style="width: {{ $pct }}%"></span></span>
                    <b>{{ $plan->completed_tasks_count }}/{{ $plan->tasks_count }} task</b>
                @else
                    <small>Belum ada task</small>
                @endif
            </span>
        </li>
    @endforeach
</ul>
