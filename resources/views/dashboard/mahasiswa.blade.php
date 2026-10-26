@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $greeting = \App\Support\Greeting::now().', '.$user->firstName();
        $running = $internship && in_array($internship->status, ['berjalan', 'selesai'], true);
    @endphp

    @if (! $internship)
        <x-hero eyebrow="Mahasiswa" :title="$greeting"
                sub="Anda belum punya KP. Ajukan KP bila sudah mendapat tempat, atau tunggu penempatan dari kampus."
                action="Ajukan KP" :href="route('kp.status')" />

        <div class="card">
            <div class="empty">
                <strong>Belum ada KP</strong>
                Setelah pengajuan Anda disetujui, timesheet, kegiatan, dan goals muncul di sini.
                <a href="{{ route('kp.status') }}">Buka Status KP</a> untuk mengajukan.
            </div>
        </div>
    @elseif (! $running)
        <x-hero eyebrow="Mahasiswa" :title="$greeting"
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
                <a href="{{ $next['url'] ?? route('kp.status') }}" class="btn btn-sm">{{ $next['url'] ? ($next['cta'] ?? 'Buka') : 'Detail' }}</a>
            </div>
        @endif
    @else
        @php($a = $internship->assessment)
        <x-hero :eyebrow="'Mahasiswa · '.$internship->placeName()" :title="$greeting"
                :sub="$internship->status === 'selesai'
                    ? 'KP Anda sudah selesai. Data kegiatan dan timesheet tetap bisa dibuka.'
                    : $period->label.'. Kerjakan task sesuai rencana dan catat kegiatan harian Anda.'"
                action="Buka kegiatan" :href="route('kegiatan.index')">
            <x-slot:meta>
                <span class="hero-tag"><x-ikon name="building" :size="15" /> {{ $internship->division?->name ? 'Divisi '.$internship->division->name : 'Divisi belum diisi' }}</span>
                <span class="hero-tag"><x-ikon name="person" :size="15" /> {{ $internship->position ?? 'Posisi belum diisi' }}</span>
                <span class="hero-tag"><x-ikon name="calendar" :size="15" /> {{ $internship->cohortLabel() }}</span>
            </x-slot:meta>
        </x-hero>

        <div class="stat-grid">
            <x-stat label="Minggu berjalan" :value="$internship->currentWeek()" :unit="'dari '.$internship->totalWeeks()"
                    :hint="'Sisa '.$internship->daysLeft().' hari · s.d. '.$internship->end_date->locale('id')->translatedFormat('j M')" />
            @php($activePlans = $plans->filter(fn ($p) => $p->phase() === 'active')->count())
            <x-stat label="Rencana kegiatan" :value="$plans->count()" unit="kegiatan" :href="route('timesheet.index')"
                    :tone="$plans->isEmpty() ? 'amber' : 'blue'" :hint="$plans->isEmpty() ? 'Isi rencana dulu di timesheet' : $activePlans.' sedang berjalan'" />
            <x-stat label="Task selesai" :value="$internship->completed_tasks_count" :unit="'dari '.$internship->tasks_count" :href="route('kegiatan.index')"
                    :percent="$internship->percent()" />
            @if ($internship->status === 'selesai' || $a?->isValidated())
                <x-stat label="Nilai akhir" :value="$a?->isValidated() ? $a->letter_grade : null" :unit="$a?->isValidated() ? \App\Support\Num::short($a->final_score) : null"
                        :tone="$a?->isValidated() ? 'green' : 'gray'" empty="Belum dinilai"
                        :hint="$a?->isValidated() ? 'Sudah divalidasi' : 'Muncul setelah divalidasi dosen pembimbing'" />
            @else
                <x-stat label="Goals tercapai" :value="$goals->whereNotNull('achieved_at')->count()" :unit="'dari '.$goals->count()" :href="route('kegiatan.index')"
                        tone="gray" :hint="$goals->isEmpty() ? 'Tulis goals di halaman Kegiatan' : 'Tandai di halaman Kegiatan'" />
            @endif
        </div>

        @if ($next && in_array($next['state'], ['current', 'waiting', 'attention'], true) && $next['key'] !== 'activities')
            <div class="next-step next-{{ $next['state'] }}">
                <x-ikon :name="$next['state'] === 'attention' ? 'alert' : ($next['state'] === 'waiting' ? 'clock' : 'arrow')" :size="20" />
                <div>
                    <div class="next-step-title">{{ ['attention' => 'Perlu tindakan', 'waiting' => 'Menunggu '.$next['actor'], 'current' => 'Giliran Anda'][$next['state']] }}: {{ $next['title'] }}</div>
                    <div class="next-step-sub">{{ $next['note'] ?? $next['detail'] }}</div>
                </div>
                <a href="{{ $next['url'] ?? route('kp.status') }}" class="btn btn-sm">{{ $next['url'] ? ($next['cta'] ?? 'Buka') : 'Buka' }}</a>
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
                            <h2 class="card-title">Rencana kegiatan</h2>
                            <p class="card-sub">Dari timesheet · garis merah hari ini</p>
                        </div>
                        <a href="{{ route('timesheet.index') }}" class="btn btn-sm">Timesheet</a>
                    </div>
                    @if ($plans->isEmpty())
                        <div class="empty">
                            <strong>Belum ada rencana</strong>
                            Susun rencana kegiatan per minggu atau per bulan di <a href="{{ route('timesheet.index') }}">Timesheet</a>, lalu buat task di bawahnya.
                        </div>
                    @else
                        @include('timesheet._gantt', ['canEdit' => false, 'compact' => true])
                    @endif
                </section>
            </div>

            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Kemajuan KP</h2>
                            <p class="card-sub">{{ collect($steps)->where('state', 'done')->count() }} dari {{ count($steps) }} langkah selesai</p>
                        </div>
                        <a href="{{ route('kp.status') }}" class="btn btn-sm">Detail</a>
                    </div>
                    <ul class="mini-steps">
                        @foreach ($steps as $step)
                            @continue($step['state'] === 'later')
                            @php($link = $step['url'] && $step['state'] !== 'upcoming')
                            <li @class(['mini-step', 'is-'.$step['state']])>
                                <span class="mini-step-dot" aria-hidden="true">@if ($step['state'] === 'done')<x-ikon name="check" :size="12" />@endif</span>
                                @if ($link)
                                    <a href="{{ $step['url'] }}" class="mini-step-title">{{ $step['title'] }}</a>
                                @else
                                    <span class="mini-step-title">{{ $step['title'] }}</span>
                                @endif
                                <span class="mini-step-state">{{ ['done' => 'Selesai', 'current' => 'Berjalan', 'waiting' => 'Menunggu', 'attention' => 'Perlu cek', 'upcoming' => 'Belum'][$step['state']] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Meeting minggu ini</h2>
                    </div>
                    @php($host = $internship->supervisor?->listName() ?? 'Company Supervisor')
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
                        <h2 class="card-title">Tempat magang</h2>
                    </div>
                    <div class="card-body">
                        <dl class="kv">
                            <div><dt>Perusahaan</dt><dd>{{ $internship->company?->name }}@if ($internship->company?->city)<small>{{ $internship->company->city }}</small>@endif</dd></div>
                            <div><dt>Divisi</dt><dd>{{ $internship->division?->name ?? '–' }}</dd></div>
                            <div><dt>Posisi</dt><dd>{{ $internship->position ?? '–' }}</dd></div>
                            <div><dt>Company Supervisor</dt><dd>{{ $internship->supervisor?->listName() ?? 'Menunggu aktivasi TU' }}@if ($internship->supervisor_title)<small>{{ $internship->supervisor_title }}</small>@endif</dd></div>
                            <div><dt>Dosen pembimbing</dt><dd>{{ $internship->lecturer?->listName() ?? '–' }}</dd></div>
                            <div><dt>Periode</dt><dd>{{ $internship->periodLabel() }}<small>Bulan ke-{{ $internship->currentMonth() }} dari {{ $internship->durationMonths() }}</small></dd></div>
                        </dl>
                    </div>
                </section>
            </div>
        </div>
    @endif
@endsection
