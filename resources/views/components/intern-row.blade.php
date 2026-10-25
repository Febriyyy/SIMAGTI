{{--
    Satu baris mahasiswa di daftar pemantauan (halaman Mahasiswa Magang dan dashboard).
    Desktop: sejajar dengan kepala kolom. Kartu sempit / HP: tersusun ke bawah, nama tidak dipotong, tombol tetap di kanan.
    Variabel: internship, last (tanggal catatan terakhir), week (jam minggu ini, opsional), compact (versi dashboard)
--}}
@props(['internship', 'last' => null, 'week' => null, 'compact' => false])
@php
    $i = $internship;
    $student = $i->student;
    $viewer = auth()->user();
    $id = $student->academicIdentity();
    $hours = (float) ($i->logged_hours ?? $i->loggedHours());
    $needsConfirm = $viewer->role === 'supervisor' && $i->status === 'berjalan' && ! $i->supervisor_confirmation;
    $syllabus = ['draft' => 'Draf', 'submitted' => 'Menunggu', 'revision' => 'Revisi', 'approved' => 'Disetujui'][$i->syllabus_status] ?? $i->syllabusStatusLabel();
    $role = $i->isSeminar() ? $i->programTypeLabel() : ($i->position ?? 'Posisi belum diisi');
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
            <small>{{ $i->isSeminar() ? 'Seminar' : $i->programTypeLabel() }}</small>
        </div>
    @endunless

    <div class="srow-cell srow-progress">
        <span class="srow-label">Task</span>
        <x-progress :value="$i->percent()" />
        <small>{{ $i->completed_tasks_count ?? 0 }}/{{ $i->tasks_count ?? 0 }} selesai</small>
    </div>

    <div class="srow-cell srow-num">
        <span class="srow-label">Jam</span>
        <span class="srow-value">{{ \App\Support\Num::short($week !== null ? (float) $week : $hours, 1) }}</span>
        <small>{{ $week !== null ? 'total '.\App\Support\Num::short($hours, 1) : 'total' }}</small>
    </div>

    @unless ($compact)
        <div class="srow-cell srow-time">
            <span class="srow-label">Minggu</span>
            <span class="srow-value">{{ $i->status === 'berjalan' ? $i->currentWeek().'/'.$i->totalWeeks() : $i->statusLabel() }}</span>
            <small>{{ $last ? 'Aktif '.mb_strtolower(\App\Support\Relative::label(\Illuminate\Support\Carbon::parse($last))) : 'Belum aktif' }}</small>
        </div>

        <div class="srow-cell srow-status">
            <span class="srow-label">Silabus</span>
            <span class="srow-chips">
                <x-chip :tone="$i->syllabusStatusTone()">{{ $syllabus }}</x-chip>
                @if ($i->result)<x-chip :tone="$i->resultTone()">{{ $i->resultLabel() }}</x-chip>@endif
            </span>
        </div>
    @endunless

    <div class="srow-actions">
        <a href="{{ route('pantau.show', $i) }}" class="btn btn-sm">Buka <x-ikon name="arrow" :size="16" /></a>
    </div>
</li>
