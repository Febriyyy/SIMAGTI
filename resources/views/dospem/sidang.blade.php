@extends('layouts.app')

@section('title', 'Jadwal Sidang')

{{--
    Dosen pembimbing: jadwal sidang KP mahasiswa bimbingannya. Nilainya disahkan kaprodi di Pengesahan Nilai KP.
    Variabel: unscheduled, scheduled, done, examiners
--}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Jadwal Sidang</h1>
            <p class="page-sub">Jadwalkan sidang mahasiswa bimbingan Anda.</p>
        </div>
    </div>

    {{-- 1. Belum dijadwalkan --}}
    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Perlu dijadwalkan</h2>
                <p class="card-sub">KP selesai atau berakhir dalam 30 hari</p>
            </div>
            @if ($unscheduled->isNotEmpty())<x-chip tone="amber">{{ $unscheduled->count() }}</x-chip>@endif
        </div>
        @if ($unscheduled->isEmpty())
            <div class="calm">
                <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
                <p>Semua mahasiswa bimbingan yang perlu sidang sudah dijadwalkan.</p>
            </div>
        @else
            <ul class="item-list">
                @foreach ($unscheduled as $i)
                    <li class="item-row defense-row">
                        <x-avatar :user="$i->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">{{ $i->status === 'selesai' ? 'Selesai' : 'Berakhir' }} {{ $i->end_date->locale('id')->translatedFormat('j M Y') }} · {{ $i->company?->name }}</div>
                        </div>
                        <div class="defense-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-open="dlg-sch-{{ $i->id }}"><x-ikon name="calendar" :size="16" /> Jadwalkan</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- 2. Terjadwal --}}
    @if ($scheduled->isNotEmpty())
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Terjadwal</h2>
                    <p class="card-sub">Nilai disahkan kaprodi setelah sidang</p>
                </div>
            </div>
            <ul class="item-list">
                @foreach ($scheduled as $i)
                    @php($s = $i->defenseSchedule)
                    <li class="item-row defense-row">
                        <span class="defense-date" aria-hidden="true">
                            <b>{{ $s->scheduled_at->format('j') }}</b>
                            <small>{{ $s->scheduled_at->locale('id')->translatedFormat('M') }}</small>
                        </span>
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">
                                <span class="sr-only">{{ $s->whenLabel() }} ·</span>{{ $s->scheduled_at->locale('id')->translatedFormat('l') }}, {{ $s->scheduled_at->format('H.i') }}
                                · {{ $s->isOnline() ? 'Daring' : $s->place }}
                                @if ($s->examiner) · Penguji {{ $s->examiner->listName() }}@endif
                            </div>
                        </div>
                        <div class="defense-actions">
                            @if ($s->hasPassed())
                                <span class="defense-soon">Menunggu pengesahan kaprodi</span>
                            @else
                                <span class="defense-soon">{{ ($d = (int) today()->diffInDays($s->scheduled_at->copy()->startOfDay())) === 0 ? 'Hari ini' : $d.' hari lagi' }}</span>
                                @if ($s->isOnline() && str_starts_with($s->place, 'http'))
                                    <a href="{{ $s->place }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost"><x-ikon name="video" :size="16" /> Gabung</a>
                                @endif
                            @endif
                            <button type="button" class="btn btn-sm btn-ghost" data-open="dlg-sch-{{ $i->id }}">Ubah jadwal</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 3. Sudah dinilai --}}
    @if ($done->isNotEmpty())
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Sudah dinilai</h2>
                    <p class="card-sub">{{ $done->count() }} mahasiswa</p>
                </div>
            </div>
            <ul class="item-list">
                @foreach ($done as $i)
                    @php($d = $i->defenseScore)
                    <li class="item-row defense-row">
                        <x-avatar :user="$i->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">Sidang {{ $d->defended_at?->locale('id')->translatedFormat('j M Y') }}</div>
                        </div>
                        <span class="sidang-score"><b>{{ $d->letter_grade }}</b>{{ \App\Support\Num::short($d->score) }}</span>
                        @if ($d->lecturer_score !== null)
                            <a href="{{ route('sidang.berita', $i) }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost"><x-ikon name="print" :size="16" /> Berita acara</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @foreach ($unscheduled->concat($scheduled) as $i)
        @include('sidang._jadwal-dialog', ['i' => $i, 'examiners' => $examiners, 'action' => 'dospem.sidang.jadwal'])
    @endforeach
@endsection
