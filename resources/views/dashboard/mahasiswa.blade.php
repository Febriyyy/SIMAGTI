@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ \App\Support\Greeting::now() }}, {{ $user->firstName() }}</h1>
            <p class="page-sub">Ringkasan kerja praktik kamu.</p>
        </div>
    </div>

    @if (! $internship)
        <div class="card">
            <div class="empty">
                <strong>Belum ada penempatan magang</strong>
                Penempatan akan muncul di sini setelah kampus dan perusahaan mengonfirmasinya.
                Mencari tempat sendiri? <a href="{{ route('pengajuan.index') }}">Ajukan KP mandiri</a>.
            </div>
        </div>
    @else
        <div class="pills">
            <span class="pill"><x-icon name="calendar" :size="16" /> Minggu ke-<strong>{{ $internship->currentWeek() }}</strong> dari {{ $internship->totalWeeks() }}</span>
            <span class="pill"><x-icon name="clock" :size="16" /> Sisa <strong>{{ $internship->daysLeft() }} hari</strong></span>
            <span class="pill"><x-icon name="tasks" :size="16" /> Task selesai <strong>{{ $internship->completed_tasks_count }}</strong> dari {{ $internship->tasks_count }}</span>
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
