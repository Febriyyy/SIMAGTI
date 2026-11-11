{{--
    Kartu per rencana kegiatan (tab Rencana di Timesheet, di atas Gantt; sekaligus keterangan warna Gantt).
    Kartu terang dengan titik warna rencana, status yang jelas (Berjalan / Selesai / Akan datang / Terlambat),
    tanggal, dan progres task. Rencana yang sedang berjalan disorot. Pemilik bisa klik kartu untuk mengubah rencana.
    Variabel: $plans (withCount tasks, completed_tasks_count), $canEdit (opsional)
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
            @if ($canEdit ?? false)
                <button type="button" class="plan-card-title plan-card-open" data-open="dlg-plan-{{ $plan->id }}" title="Ubah rencana {{ $plan->title }}">{{ $plan->title }}</button>
            @else
                <span class="plan-card-title" title="{{ $plan->title }}">{{ $plan->title }}</span>
            @endif
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
