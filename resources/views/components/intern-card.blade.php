{{--
    Kartu satu mahasiswa di halaman pemantauan (/pantau). Ringkas: nama, posisi, progres task, minggu, rencana,
    aktif terakhir, dan status penilaian. Seluruh kartu bisa diklik ke halaman Buka; detail lengkap ada di sana.
    Variabel: internship, last (tanggal catatan terakhir), cohort (label kloter, opsional)
--}}
@props(['internship', 'last' => null, 'cohort' => null])
@php
    $i = $internship;
    $student = $i->student;
    $viewer = auth()->user();
    $plans = (int) ($i->activity_plans_count ?? $i->activityPlans()->count());
    $needsConfirm = $viewer->role === 'supervisor' && $i->status === 'berjalan' && ! $i->supervisor_confirmation;
    $assessment = $i->relationLoaded('assessment') ? $i->assessment : null;
    $lastLabel = $last ? mb_strtolower(\App\Support\Relative::label(\Illuminate\Support\Carbon::parse($last))) : null;
    $idle = $i->status === 'berjalan' && (! $last || \Illuminate\Support\Carbon::parse($last)->lt(now()->subDays(7)));
@endphp
<li @class(['icard', 'is-flagged' => $needsConfirm])>
    <div class="icard-head">
        <x-avatar :user="$student" />
        <div class="icard-who">
            <a href="{{ route('pantau.show', $i) }}" class="icard-name" title="{{ $student->name }}">{{ $student->listName() }}</a>
            <span class="icard-role">{{ $i->position ?? 'Posisi belum diisi' }}</span>
        </div>
        @if ($needsConfirm)<span class="icard-flag">Perlu konfirmasi</span>@endif
    </div>

    @if ($cohort)
        <p class="icard-cohort"><span class="cohort-badge">{{ $cohort }}</span> <span>{{ $i->periodLabel() }}</span></p>
    @endif

    <div class="icard-progress">
        <div class="icard-progress-top">
            <span>Task selesai</span>
            <strong>{{ $i->completed_tasks_count ?? 0 }}<small>/{{ $i->tasks_count ?? 0 }}</small></strong>
        </div>
        <x-progress :value="$i->percent()" inside />
    </div>

    <dl class="icard-facts">
        <div>
            <dt>Minggu</dt>
            <dd>{{ $i->status === 'berjalan' ? $i->currentWeek().'/'.$i->totalWeeks() : $i->statusLabel() }}</dd>
        </div>
        <div>
            <dt>Rencana</dt>
            <dd>{{ $plans }}</dd>
        </div>
        <div>
            <dt>Aktif</dt>
            <dd @class(['is-idle' => $idle])>{{ $lastLabel ?? 'Belum' }}</dd>
        </div>
    </dl>

    <div class="icard-foot">
        @if ($i->result)
            <x-chip :tone="$i->resultTone()">{{ $i->resultLabel() }}</x-chip>
        @elseif ($assessment)
            <x-chip :tone="$assessment->statusTone()">{{ $assessment->status === 'validated' ? 'Nilai '.$assessment->letter() : $assessment->statusLabel() }}</x-chip>
        @else
            <x-chip tone="gray">Belum dinilai</x-chip>
        @endif
        <a href="{{ route('pantau.show', $i) }}" class="btn btn-sm icard-open" aria-label="Buka {{ $student->listName() }}">Buka</a>
    </div>
</li>
