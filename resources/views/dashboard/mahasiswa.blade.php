@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $greeting = \App\Support\Greeting::now().', '.$user->firstName();
        $running = $internship && in_array($internship->status, ['berjalan', 'selesai'], true);
    @endphp

    @if (! $internship)
        <x-hero eyebrow="Mahasiswa" :title="$greeting"
                sub="Belum ada KP."
                action="Ajukan KP" :href="route('kp.status')" />

        <div class="card">
            <div class="empty">
                <strong>Belum ada KP</strong>
                <a href="{{ route('kp.status') }}">Ajukan KP</a> untuk mulai.
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
                    ? 'KP Anda sudah selesai.'
                    : $period->label"
                action="Kegiatan" :href="route('kegiatan.index')">
            <x-slot:meta>
                <span class="hero-tag"><x-ikon name="building" :size="15" /> {{ $internship->division?->name ? 'Divisi '.$internship->division->name : 'Divisi belum diisi' }}</span>
                <span class="hero-tag"><x-ikon name="person" :size="15" /> {{ $internship->position ?? 'Posisi belum diisi' }}</span>
                <span class="hero-tag"><x-ikon name="calendar" :size="15" /> {{ $internship->cohortLabel() }}</span>
            </x-slot:meta>
        </x-hero>

        {{-- Tawaran kerja dari tempat magang: tampil hanya saat menunggu jawaban mahasiswa --}}
        @php($offer = $internship->conversion()->where('status', 'ditawari')->first())
        @if ($offer)
            <section class="offer-card" aria-labelledby="offer-title">
                <span class="offer-icon" aria-hidden="true"><x-ikon name="star" :size="22" fill /></span>
                <div class="min0 grow">
                    <h2 id="offer-title" class="offer-title">Tawaran kerja dari {{ $internship->placeName() }}</h2>
                    <p class="offer-sub">{{ $offer->offered_position }}@if ($offer->respond_by) · jawab sebelum {{ $offer->respond_by->locale('id')->translatedFormat('j F') }}@endif</p>
                </div>
                <div class="offer-actions">
                    <a href="{{ route('rekrutmen.tawaran') }}" class="btn btn-primary">Lihat tawaran <x-ikon name="arrow" :size="16" /></a>
                </div>
            </section>
        @endif

        {{-- Kartu Kemajuan KP: elemen khas dashboard mahasiswa (minggu berjalan, task, dan langkah KP dalam satu kartu) --}}
        @php($visibleSteps = collect($steps)->reject(fn ($st) => $st['state'] === 'later')->values())
        @php($doneSteps = $visibleSteps->where('state', 'done')->count())
        @php($nowStep = $visibleSteps->first(fn ($st) => in_array($st['state'], ['current', 'waiting', 'attention'], true)))
        @php($taskPct = $internship->percent())
        <section class="kpp" aria-labelledby="kpp-title">
            <div class="kpp-top">
                <div class="kpp-main">
                    <span class="kpp-eyebrow">Kemajuan KP</span>
                    <h2 id="kpp-title" class="kpp-title">
                        @if ($internship->status === 'selesai')
                            KP selesai
                        @else
                            Minggu ke-{{ $internship->currentWeek() }} <span>dari {{ $internship->totalWeeks() }}</span>
                        @endif
                    </h2>
                    <p class="kpp-sub">
                        @if ($internship->status !== 'selesai')Sisa {{ $internship->daysLeft() }} hari @else{{ $doneSteps }}/{{ $visibleSteps->count() }} langkah @endif
                    </p>
                </div>
                <div class="kpp-figures">
                    <a href="{{ route('kegiatan.index') }}" class="kpp-ring" style="--p: {{ $taskPct }}" aria-label="Task selesai {{ $taskPct }} persen, buka Kegiatan">
                        <strong>{{ $taskPct }}%</strong>
                        <span>task selesai</span>
                    </a>
                    <div class="kpp-fig">
                        <strong>{{ $internship->completed_tasks_count }}<small>/{{ $internship->tasks_count }}</small></strong>
                        <span>Task selesai</span>
                    </div>
                </div>
            </div>

            <ol class="mini-steps kpp-steps" style="--n: {{ max(1, $visibleSteps->count()) }}">
                @foreach ($visibleSteps as $step)
                    @php($link = $step['url'] && $step['state'] !== 'upcoming')
                    <li @class(['mini-step', 'is-'.$step['state']]) @if ($step === $nowStep) aria-current="step" @endif>
                        <span class="mini-step-dot" aria-hidden="true">@if ($step['state'] === 'done')<x-ikon name="tick" :size="12" />@endif</span>
                        @if ($link)
                            <a href="{{ $step['url'] }}" class="mini-step-title">{{ $step['title'] }}</a>
                        @else
                            <span class="mini-step-title">{{ $step['title'] }}</span>
                        @endif
                        <span class="mini-step-state">{{ ['done' => 'Selesai', 'current' => 'Berjalan', 'waiting' => 'Menunggu', 'attention' => 'Perlu cek', 'upcoming' => 'Belum'][$step['state']] ?? '' }}</span>
                    </li>
                @endforeach
            </ol>

            <div class="kpp-foot">
                @if ($nowStep)
                    <span class="kpp-now">Sekarang: <strong>{{ $nowStep['title'] }}</strong></span>
                @else
                    <span class="kpp-now">Semua langkah yang berjalan sudah selesai.</span>
                @endif
                <a href="{{ route('kp.status') }}" class="kpp-link">Detail langkah <x-ikon name="arrow" :size="16" /></a>
            </div>
        </section>

        {{-- Kartu angka terpisah (acuan referensi: ikon + judul, angka di kotak dalam) --}}
        @if ($internship->status === 'selesai')
            {{-- KP Wrapped: ajakan melihat rangkuman animasi akhir KP --}}
            <a href="{{ route('wrapped') }}" class="wrapped-cta">
                <span class="wrapped-cta-badge" aria-hidden="true">{{ $internship->end_date->format('Y') }}</span>
                <span class="min0">
                    <strong>KP Wrapped kamu sudah siap</strong>
                    <span>Rangkuman perjalanan KP-mu.</span>
                </span>
                <span class="wrapped-cta-go">Putar <x-ikon name="arrow" :size="16" /></span>
            </a>
        @endif

        <div class="stat-grid dash-stats">
            @php($activePlans = $plans->filter(fn ($p) => $p->phase() === 'active')->count())
            <x-stat label="Rencana kegiatan" :value="$plans->count()" unit="kegiatan" :href="route('timesheet.index')"
                    :tone="$plans->isEmpty() ? 'amber' : 'blue'" :hint="$plans->isEmpty() ? 'Isi di Timesheet' : $activePlans.' sedang berjalan'" />
            {{-- Nilai akhir baru tampil setelah magang, laporan KP, dan sidang KP selesai lalu hasil KP diputuskan --}}
            @if ($internship->final_grade)
                <x-stat label="Nilai akhir" :value="$internship->final_grade" :unit="$internship->final_score !== null ? \App\Support\Num::short((float) $internship->final_score) : null"
                        tone="green" hint="Nilai sidang KP" />
            @elseif ($internship->status === 'selesai' || $a?->isValidated())
                <x-stat label="Nilai akhir" :value="null" tone="gray" empty="Belum keluar"
                        :hint="$internship->defenseScore ? 'Menunggu hasil KP' : 'Keluar setelah sidang KP'" />
            @else
                <x-stat label="Goals tercapai" :value="$goals->whereNotNull('achieved_at')->count()" :unit="'dari '.$goals->count()" :href="route('kegiatan.index')"
                        tone="gray" :hint="$goals->isEmpty() ? 'Tulis di Kegiatan' : 'Tandai di Kegiatan'" />
            @endif
            <x-stat label="Sisa waktu KP" :value="$internship->status === 'selesai' ? null : $internship->daysLeft()" unit="hari" empty="Selesai"
                    :tone="$internship->status !== 'selesai' && $internship->daysLeft() <= 21 ? 'amber' : 'gray'"
                    :hint="'s.d. '.$internship->end_date->locale('id')->translatedFormat('j M Y')" />
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

        {{-- Baris bento: kiri lebar, kanan sempit; kartu dalam satu baris sama tinggi --}}
        <div class="dash-row">
            <section class="card dash-plans" aria-labelledby="plans-title">
                <div class="card-head">
                    <h2 class="card-title" id="plans-title">Rencana kegiatan <span class="dash-count">{{ $plans->count() }}</span></h2>
                    <a href="{{ route('timesheet.index') }}" class="dash-more">Timesheet <x-ikon name="arrow-up-right" :size="16" /></a>
                </div>
                @if ($plans->isEmpty())
                    <div class="empty">
                        <strong>Belum ada rencana</strong>
                        <a href="{{ route('timesheet.index') }}">Susun rencana</a> dulu, lalu buat task di bawahnya.
                    </div>
                @else
                    @include('timesheet._plan-cards', ['plans' => $plans])
                @endif
            </section>

            <section class="card" aria-labelledby="place-title">
                <div class="card-head">
                    <h2 class="card-title" id="place-title">Tempat magang</h2>
                </div>
                {{-- Perusahaan di atas, divisi + posisi sebagai pil, dua pendamping dengan avatar, periode dengan batang bulan --}}
                <div class="place">
                    <div class="place-org">
                        <x-org :name="$internship->company?->name ?? '?'" />
                        <div class="min0">
                            <div class="place-company">{{ $internship->company?->name }}</div>
                            @if ($internship->company?->city)
                                <div class="place-city">{{ $internship->company->city }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="place-tags">
                        <span class="place-tag"><x-ikon name="building" :size="14" /> {{ $internship->division?->name ?? 'Divisi belum diisi' }}</span>
                        <span class="place-tag"><x-ikon name="person" :size="14" /> {{ $internship->position ?? 'Posisi belum diisi' }}</span>
                    </div>

                    <ul class="place-people">
                        <li>
                            @if ($internship->supervisor)<x-avatar :user="$internship->supervisor" />@else<span class="avatar avatar-empty" aria-hidden="true">?</span>@endif
                            <div class="min0">
                                <span class="place-role">Company Supervisor</span>
                                <span class="place-name">{{ $internship->supervisor?->listName() ?? 'Menunggu aktivasi TU' }}</span>
                                @if ($internship->supervisor_title)<span class="place-sub">{{ $internship->supervisor_title }}</span>@endif
                            </div>
                        </li>
                        <li>
                            @if ($internship->lecturer)<x-avatar :user="$internship->lecturer" />@else<span class="avatar avatar-empty" aria-hidden="true">?</span>@endif
                            <div class="min0">
                                <span class="place-role">Dosen pembimbing</span>
                                <span class="place-name">{{ $internship->lecturer?->listName() ?? 'Belum ditunjuk' }}</span>
                            </div>
                        </li>
                    </ul>

                    @php($months = max(1, $internship->durationMonths()))
                    <div class="place-period">
                        <div class="place-period-top">
                            <span>Periode</span>
                            <b>{{ $internship->periodLabel() }}</b>
                        </div>
                        <span class="place-bar" role="progressbar" aria-valuenow="{{ $internship->currentMonth() }}" aria-valuemin="0" aria-valuemax="{{ $months }}" aria-label="Bulan KP"><span style="width: {{ min(100, round($internship->currentMonth() / $months * 100)) }}%"></span></span>
                        <span class="place-sub">Bulan ke-{{ $internship->currentMonth() }} dari {{ $months }}</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="dash-row">
            <section class="card" aria-labelledby="todo-title">
                <div class="card-head">
                    <div>
                        <h2 class="card-title" id="todo-title">Perlu dikerjakan</h2>
                        <p class="card-sub">Tenggat akhir {{ mb_strtolower($period->short) }}</p>
                    </div>
                    <a href="{{ route('kegiatan.index') }}" class="dash-more">Board <x-ikon name="arrow-up-right" :size="16" /></a>
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

            <section class="card dash-meet" aria-labelledby="meet-title">
                <div class="card-head">
                    <h2 class="card-title" id="meet-title">Meeting minggu ini</h2>
                </div>
                @php($host = $internship->supervisor?->listName() ?? 'Company Supervisor')
                @if ($meeting)
                    <div class="dash-meet-body">
                        <div class="dash-meet-when">
                            <span class="dash-meet-icon" aria-hidden="true"><x-ikon name="video" :size="20" /></span>
                            <div>
                                <div class="meeting-when">{{ $meeting->dayLabel() }}, {{ $meeting->timeLabel() }}</div>
                                <div class="meeting-who">Bersama {{ $host }}</div>
                            </div>
                        </div>
                        <a href="{{ $meeting->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                            <x-ikon name="video" :size="16" /> Gabung meeting
                        </a>
                    </div>
                @else
                    <p class="meeting-empty">Belum ada jadwal meeting minggu ini. {{ $host }} akan mengaturnya.</p>
                @endif
            </section>
        </div>
    @endif
@endsection
