{{--
    Satu baris mahasiswa di daftar pemantauan (halaman Mahasiswa Magang dan dashboard).
    Desktop: sejajar dengan kepala kolom. Kartu sempit / HP: tersusun ke bawah, nama tidak dipotong, tombol tetap di kanan.
    Variabel: internship, last (tanggal catatan terakhir), week (catatan kegiatan minggu ini, opsional), compact (versi dashboard)
--}}
@props(['internship', 'last' => null, 'week' => null, 'compact' => false])
@php
    $i = $internship;
    $student = $i->student;
    $viewer = auth()->user();
    $id = $student->academicIdentity();
    $plans = (int) ($i->activity_plans_count ?? $i->activityPlans()->count());
    $needsConfirm = $viewer->role === 'supervisor' && $i->status === 'berjalan' && ! $i->supervisor_confirmation;
    $role = $i->position ?? 'Posisi belum diisi';
    $assessment = $i->relationLoaded('assessment') ? $i->assessment : null;
@endphp
<li {{ $attributes->class(['srow', 'srow-compact' => $compact]) }}>
    <a href="{{ route('pantau.show', $i) }}" class="srow-who">
        <x-avatar :user="$student" />
        <span class="srow-name">
            <strong title="{{ $student->name }}">{{ $student->listName() }}</strong>
            <small>{{ $student->nim }}@if ($id['entry_year']) · Angkatan {{ $id['entry_year'] }}@endif</small>
            @unless ($compact)<small>{{ $id['program'] ?? 'Prodi belum diisi' }}</small>@endunless
            @if ($compact)<small>{{ $role }}</small>@endif
            @if ($needsConfirm)<span class="srow-flag">Perlu konfirmasi</span>@endif
        </span>
    </a>

    @unless ($compact)
        <div class="srow-cell srow-pos">
            <span class="srow-label">Posisi</span>
            <span class="srow-value">{{ $role }}</span>
            <small>{{ $i->division?->name ? 'Divisi '.$i->division->name : 'Divisi belum diisi' }}</small>
        </div>
    @endunless

    <div class="srow-cell srow-progress">
        <span class="srow-label">Task</span>
        <x-progress :value="$i->percent()" />
        <small>{{ $i->completed_tasks_count ?? 0 }}/{{ $i->tasks_count ?? 0 }} selesai</small>
    </div>

    <div class="srow-cell srow-num">
        <span class="srow-label">{{ $week !== null ? 'Catatan' : 'Rencana' }}</span>
        <span class="srow-value">{{ $week !== null ? (int) $week : $plans }}</span>
        <small>{{ $week !== null ? 'minggu ini' : 'kegiatan' }}</small>
    </div>

    @unless ($compact)
        <div class="srow-cell srow-time">
            <span class="srow-label">Minggu</span>
            <span class="srow-value">{{ $i->status === 'berjalan' ? $i->currentWeek().'/'.$i->totalWeeks() : $i->statusLabel() }}</span>
            <small>{{ $last ? 'Aktif '.mb_strtolower(\App\Support\Relative::label(\Illuminate\Support\Carbon::parse($last))) : 'Belum aktif' }}</small>
        </div>

        <div class="srow-cell srow-status">
            <span class="srow-label">Penilaian</span>
            <span class="srow-chips">
                @if ($i->result)
                    <x-chip :tone="$i->resultTone()">{{ $i->resultLabel() }}</x-chip>
                @elseif ($assessment)
                    <x-chip :tone="$assessment->statusTone()">{{ $assessment->status === 'validated' ? 'Nilai '.$assessment->letter() : $assessment->statusLabel() }}</x-chip>
                @else
                    <x-chip tone="gray">Belum dinilai</x-chip>
                @endif
            </span>
        </div>
    @endunless

    <div class="srow-actions">
        <a href="{{ route('pantau.show', $i) }}" class="btn btn-sm">Buka <x-ikon name="arrow" :size="16" /></a>
    </div>
</li>
