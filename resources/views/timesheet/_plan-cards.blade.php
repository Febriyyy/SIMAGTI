{{-- Kartu berwarna per rencana kegiatan (dashboard dan Timesheet). Variabel: $plans (withCount tasks, completed_tasks_count) --}}
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
