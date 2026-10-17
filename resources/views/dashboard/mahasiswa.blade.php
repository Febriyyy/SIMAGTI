@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if ($internship)
        <x-hero eyebrow="Mahasiswa · {{ $internship->company->name }}"
                :title="\App\Support\Greeting::now().', '.$user->firstName()"
                :sub="'Minggu ke-'.$internship->currentWeek().' dari '.$internship->totalWeeks().'. Kerjakan task minggu ini dan lampirkan buktinya.'"
                action="Lihat task minggu ini" :href="route('tugas.index')" />
    @else
        <x-hero eyebrow="Mahasiswa"
                :title="\App\Support\Greeting::now().', '.$user->firstName()"
                sub="Kamu belum punya penempatan KP. Ajukan KP mandiri jika sudah menemukan tempat."
                action="Ajukan KP mandiri" :href="route('pengajuan.index')" />
    @endif

    @if (! $internship)
        <div class="card">
            <div class="empty">
                <strong>Belum ada penempatan magang</strong>
                Penempatan akan muncul di sini setelah kampus dan perusahaan mengonfirmasinya.
                Mencari tempat sendiri? <a href="{{ route('pengajuan.index') }}">Ajukan KP mandiri</a>.
            </div>
        </div>
    @else
        @php($a = $internship->assessment)
        <div class="stat-grid">
            <x-stat label="Minggu berjalan" :value="$internship->currentWeek()" :unit="'dari '.$internship->totalWeeks()" icon="calendar"
                    :hint="$internship->start_date->locale('id')->translatedFormat('j M').' – '.$internship->end_date->locale('id')->translatedFormat('j M Y')" />
            <x-stat label="Sisa waktu" :value="$internship->daysLeft()" unit="hari" icon="clock"
                    :tone="$internship->daysLeft() <= 14 && $internship->status === 'berjalan' ? 'amber' : 'blue'" hint="Sampai KP selesai" />
            <x-stat label="Task selesai" :value="$internship->completed_tasks_count" :unit="'dari '.$internship->tasks_count" icon="tasks"
                    :percent="$internship->percent()" />
            <x-stat label="Nilai akhir" :value="$a?->isValidated() ? $a->letter_grade : null" :unit="$a?->isValidated() ? \App\Support\Num::short($a->final_score) : null"
                    icon="award" :tone="$a?->isValidated() ? 'green' : 'gray'" empty="Belum dinilai"
                    :hint="$a?->isValidated() ? 'Sudah divalidasi' : 'Muncul setelah divalidasi'" />
        </div>

        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Task minggu ini</h2>
                            <p class="card-sub">Minggu ke-{{ $internship->currentWeek() }}</p>
                        </div>
                        <a href="{{ route('tugas.index') }}" class="btn btn-sm">Semua task</a>
                    </div>

                    @if ($tasks->isEmpty())
                        <div class="empty">
                            <strong>Belum ada task untuk minggu ini</strong>
                            Mentor akan menambahkannya.
                        </div>
                    @else
                        <x-task-table :tasks="$tasks" />
                    @endif
                </section>
            </div>

            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Meeting minggu ini</h2>
                    </div>
                    @if ($meeting)
                        <div class="meeting">
                            <div class="meeting-when">{{ $meeting->dayLabel() }}, {{ $meeting->timeLabel() }}</div>
                            <div class="meeting-who">Bersama {{ $internship->mentor?->name ?? 'mentor' }}</div>
                            <a href="{{ $meeting->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                <x-icon name="video" :size="16" /> Gabung meeting
                            </a>
                        </div>
                    @else
                        <p class="meeting-empty">Belum ada jadwal meeting. Mentor akan mengaturnya.</p>
                    @endif
                </section>

                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Task selesai per minggu</h2>
                    </div>
                    @if ($weekly->isEmpty())
                        <p class="meeting-empty">Belum ada data.</p>
                    @else
                        <div class="bars">
                            @foreach ($weekly as $row)
                                @php($height = $row->total > 0 ? max(4, (int) round($row->done / $row->total * 100)) : 4)
                                <div class="bar {{ (int) $row->week_number === $internship->currentWeek() ? 'is-now' : '' }}" title="Minggu {{ $row->week_number }}: {{ (int) $row->done }} dari {{ $row->total }} task selesai">
                                    <span class="bar-fill" style="height: {{ $height }}%"></span>
                                    <span class="bar-label">{{ $row->week_number }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="bars-note">Angka di bawah batang adalah nomor minggu.</p>
                    @endif
                </section>

                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Tempat magang</h2>
                    </div>
                    <div class="card-body">
                        <dl class="kv">
                            <div><dt>Perusahaan</dt><dd>{{ $internship->company->name }}</dd></div>
                            <div><dt>Mentor</dt><dd>{{ $internship->mentor?->name ?? '–' }}</dd></div>
                            <div><dt>Ketua divisi</dt><dd>{{ $internship->supervisor?->name ?? '–' }}</dd></div>
                            <div><dt>Dosen pembimbing</dt><dd>{{ $internship->lecturer?->name ?? '–' }}</dd></div>
                            <div>
                                <dt>Periode</dt>
                                <dd>
                                    {{ $internship->start_date->locale('id')->translatedFormat('j M Y') }} – {{ $internship->end_date->locale('id')->translatedFormat('j M Y') }}
                                    <small>Bulan ke-{{ $internship->currentMonth() }} dari {{ $internship->durationMonths() }}</small>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>
        </div>
    @endif
@endsection
