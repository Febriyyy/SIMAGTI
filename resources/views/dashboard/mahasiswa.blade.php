@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $greeting = \App\Support\Greeting::now().', '.$user->firstName();
        $running = $internship && in_array($internship->status, ['berjalan', 'selesai'], true);
    @endphp

    @if (! $internship)
        <x-hero eyebrow="Mahasiswa" :title="$greeting"
                sub="Anda belum punya KP. Ajukan KP Industri, MBKM Mandiri, atau Seminar untuk memulai."
                action="Ajukan KP" :href="route('kp.status')" />

        <div class="card">
            <div class="empty">
                <strong>Belum ada KP</strong>
                Setelah pengajuan Anda disetujui, kegiatan, timesheet, dan silabus muncul di sini.
                <a href="{{ route('kp.status') }}">Buka Status KP</a> untuk mengajukan.
            </div>
        </div>
    @elseif (! $running)
        <x-hero :eyebrow="'Mahasiswa · '.$internship->programTypeLabel()" :title="$greeting"
                :sub="$internship->status === 'ditolak'
                    ? 'Pengajuan KP Anda tidak disetujui. Lihat catatannya lalu ajukan ulang.'
                    : 'Pengajuan KP Anda sedang diproses'.($next ? ': '.mb_strtolower($next['title']).' oleh '.$next['actor'].'.' : '.')"
                action="Lihat Status KP" :href="route('kp.status')" />

        @if ($next)
            <div class="next-step next-{{ $next['state'] }}">
                <x-ikon :name="$next['state'] === 'attention' ? 'alert' : 'clock'" :size="20" />
                <div>
                    <div class="next-step-title">{{ $next['title'] }}</div>
                    <div class="next-step-sub">{{ $next['detail'] }}</div>
                </div>
                <a href="{{ route('kp.status') }}" class="btn btn-sm">Detail</a>
            </div>
        @endif
    @else
        @php($a = $internship->assessment)
        <x-hero :eyebrow="'Mahasiswa · '.$internship->placeName()" :title="$greeting"
                :sub="$internship->status === 'selesai'
                    ? 'KP Anda sudah selesai. Data kegiatan dan timesheet tetap bisa dibuka.'
                    : $period->label.'. Catat kegiatan dan jam kerja Anda setiap hari.'"
                action="Buka kegiatan" :href="route('kegiatan.index')">
            <x-slot:meta>
                <span class="hero-tag"><x-ikon name="school" :size="15" /> {{ $internship->programTypeLabel() }}</span>
                @if ($internship->isSeminar())
                    @if ($internship->organizer)<span class="hero-tag"><x-ikon name="users" :size="15" /> {{ $internship->organizer }}</span>@endif
                @else
                    <span class="hero-tag"><x-ikon name="building" :size="15" /> {{ $internship->division?->name ? 'Divisi '.$internship->division->name : 'Divisi belum diisi' }}</span>
                    <span class="hero-tag"><x-ikon name="person" :size="15" /> {{ $internship->position ?? 'Posisi belum diisi' }}</span>
                @endif
                <span class="hero-tag"><x-ikon name="calendar" :size="15" /> {{ $internship->cohortLabel() }}</span>
            </x-slot:meta>
        </x-hero>

        <div class="stat-grid">
            <x-stat label="Minggu berjalan" :value="$internship->currentWeek()" :unit="'dari '.$internship->totalWeeks()"
                    :hint="'Sisa '.$internship->daysLeft().' hari · s.d. '.$internship->end_date->locale('id')->translatedFormat('j M')" />
            <x-stat label="Jam tercatat" :value="\App\Support\Num::short($internship->loggedHours(), 1)" unit="jam" :href="route('timesheet.index')"
                    :percent="$internship->isMbkm() ? min(100, (int) round($internship->loggedHours() / max(1, \App\Support\Campus::rule('mbkm_min_hours')) * 100)) : null"
                    :hint="$internship->isMbkm() ? null : \App\Support\Num::short($periodHours, 1).' jam di periode ini'" />
            <x-stat label="Task selesai" :value="$internship->completed_tasks_count" :unit="'dari '.$internship->tasks_count" :href="route('kegiatan.index')"
                    :percent="$internship->percent()" />
            @if ($internship->status === 'selesai' || $a?->isValidated())
                <x-stat label="Nilai akhir" :value="$a?->isValidated() ? $a->letter_grade : null" :unit="$a?->isValidated() ? \App\Support\Num::short($a->final_score) : null"
                        :tone="$a?->isValidated() ? 'green' : 'gray'" empty="Belum dinilai"
                        :hint="$a?->isValidated() ? 'Sudah divalidasi' : 'Muncul setelah divalidasi dosen pembimbing'" />
            @else
                <x-stat label="Silabus" class="stat-text" :value="$internship->syllabusStatusLabel()" :href="route('silabus.index')"
                        :tone="$internship->syllabusStatusTone() === 'red' ? 'amber' : ($internship->syllabus_status === 'approved' ? 'green' : 'gray')"
                        :hint="$internship->syllabus_status === 'approved' ? 'Sudah disetujui' : 'Disetujui '.($internship->isSeminar() ? 'dosen pembimbing' : 'Company Supervisor')" />
            @endif
        </div>

        @if ($internship->isMbkm())
            @php($projected = $internship->projectedHours())
            <p class="chart-note" style="margin-top: calc(var(--space-2) * -1)">
                MBKM Mandiri: logbook minimal {{ \App\Support\Campus::rule('mbkm_min_hours') }} jam. Batang di kartu "Jam tercatat" menunjukkan capaian Anda.
                @if ($projected !== null)
                    <span @class(['text-red' => $internship->isBehindMbkmHours()])>Dengan laju sekarang, perkiraan di akhir KP {{ \App\Support\Num::short($projected) }} jam{{ $internship->isBehindMbkmHours() ? ', masih kurang dari syarat.' : '.' }}</span>
                @endif
            </p>
        @endif

        @php($idleGoals = $internship->status === 'berjalan' ? $internship->syllabusItems()->whereDoesntHave('tasks')->count() : 0)
        @if ($idleGoals > 0 && $internship->syllabus_status === 'approved')
            <p class="chart-note"><x-ikon name="syllabus" :size="14" /> {{ $idleGoals }} tujuan silabus belum punya task. Hubungkan task ke tujuan itu di <a href="{{ route('kegiatan.index') }}">Kegiatan</a> agar kemajuannya terlihat.</p>
        @endif

        @if ($next && in_array($next['state'], ['waiting', 'attention'], true))
            <div class="next-step next-{{ $next['state'] }}">
                <x-ikon :name="$next['state'] === 'attention' ? 'alert' : 'clock'" :size="20" />
                <div>
                    <div class="next-step-title">{{ $next['state'] === 'attention' ? 'Perlu tindakan' : 'Menunggu '.$next['actor'] }}: {{ $next['title'] }}</div>
                    <div class="next-step-sub">{{ $next['note'] ?? $next['detail'] }}</div>
                </div>
                <a href="{{ $next['key'] === 'syllabus' ? route('silabus.index') : route('kp.status') }}" class="btn btn-sm">Buka</a>
            </div>
        @endif

        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Perlu dikerjakan</h2>
                            <p class="card-sub">Belum selesai, tenggat sampai akhir {{ mb_strtolower($period->short) }}</p>
                        </div>
                        <a href="{{ route('kegiatan.index') }}" class="btn btn-sm">Board</a>
                    </div>

                    @if ($tasks->isEmpty())
                        <div class="empty">
                            <strong>Tidak ada task yang mendesak</strong>
                            Tambahkan task baru di halaman <a href="{{ route('kegiatan.index') }}">Kegiatan</a>.
                        </div>
                    @else
                        <ul class="task-mini">
                            @foreach ($tasks as $task)
                                <li>
                                    <a href="{{ route('tugas.show', $task) }}" class="task-mini-row">
                                        <span class="kcard-key">{{ $task->key() }}</span>
                                        <span class="task-mini-title truncate" title="{{ $task->title }}">{{ $task->title }}</span>
                                        <span class="prio prio-{{ $task->priority }}" title="Prioritas {{ $task->priorityLabel() }}"><x-ikon name="flag" :size="14" :fill="in_array($task->priority, ['urgent', 'high'], true)" /></span>
                                        <x-chip :tone="$task->column?->tone() ?? $task->statusTone()">{{ $task->column?->name ?? $task->statusLabel() }}</x-chip>
                                        <span @class(['task-mini-due', 'is-late' => $task->isOverdue()])>{{ $task->due_date?->locale('id')->translatedFormat('j M') ?? '–' }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Jam kegiatan per minggu</h2>
                            <p class="card-sub">Dari timesheet, 8 minggu terakhir</p>
                        </div>
                        <a href="{{ route('timesheet.index') }}" class="btn btn-sm">Timesheet</a>
                    </div>
                    <div class="card-body">
                        @if (array_sum($hours['values']) == 0)
                            <p class="muted">Belum ada jam yang dicatat. Isi timesheet setiap hari kerja.</p>
                        @else
                            <x-line-chart :width="560" :height="200" unit="jam" :labels="$hours['labels']" :tips="$hours['tips']"
                                          :series="[['name' => 'Jam tercatat', 'values' => $hours['values'], 'color' => 'green', 'area' => true]]" />
                        @endif
                    </div>
                </section>
            </div>

            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Meeting minggu ini</h2>
                    </div>
                    @php($host = $internship->isSeminar() ? ($internship->lecturer?->listName() ?? 'dosen pembimbing') : ($internship->supervisor?->listName() ?? 'Company Supervisor'))
                    @if ($meeting)
                        <div class="meeting">
                            <div class="meeting-when">{{ $meeting->dayLabel() }}, {{ $meeting->timeLabel() }}</div>
                            <div class="meeting-who">Bersama {{ $host }}</div>
                            <a href="{{ $meeting->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                <x-ikon name="video" :size="16" /> Gabung meeting
                            </a>
                        </div>
                    @else
                        <p class="meeting-empty">Belum ada jadwal meeting minggu ini. {{ $host }} akan mengaturnya.</p>
                    @endif
                </section>

                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">{{ $internship->isSeminar() ? 'Kegiatan seminar' : 'Tempat magang' }}</h2>
                    </div>
                    <div class="card-body">
                        <dl class="kv">
                            @if ($internship->isSeminar())
                                <div><dt>Kegiatan</dt><dd>{{ $internship->topic }}</dd></div>
                                <div><dt>Penyelenggara</dt><dd>{{ $internship->organizer ?? '–' }}</dd></div>
                            @else
                                <div><dt>Perusahaan</dt><dd>{{ $internship->company?->name }}@if ($internship->company?->city)<small>{{ $internship->company->city }}</small>@endif</dd></div>
                                <div><dt>Divisi</dt><dd>{{ $internship->division?->name ?? '–' }}</dd></div>
                                <div><dt>Posisi</dt><dd>{{ $internship->position ?? '–' }}</dd></div>
                                <div><dt>Company Supervisor</dt><dd>{{ $internship->supervisor?->listName() ?? 'Menunggu aktivasi TU' }}@if ($internship->supervisor_title)<small>{{ $internship->supervisor_title }}</small>@endif</dd></div>
                            @endif
                            <div><dt>Dosen pembimbing</dt><dd>{{ $internship->lecturer?->listName() ?? '–' }}</dd></div>
                            <div><dt>Periode</dt><dd>{{ $internship->periodLabel() }}<small>Bulan ke-{{ $internship->currentMonth() }} dari {{ $internship->durationMonths() }}</small></dd></div>
                        </dl>
                    </div>
                </section>
            </div>
        </div>
    @endif
@endsection
