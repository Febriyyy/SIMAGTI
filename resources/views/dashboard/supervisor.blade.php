@extends('layouts.app')

@section('title', 'Dashboard Company Supervisor')

@section('content')
    <x-hero eyebrow="Company Supervisor"
            :title="\App\Support\Greeting::now().', '.$user->firstName()"
            :sub="$stats['syllabus'] > 0
                ? $stats['syllabus'].' silabus mahasiswa menunggu persetujuan Anda.'
                : ($stats['students'] > 0 ? 'Pantau kegiatan dan timesheet mahasiswa magang Anda.' : 'Belum ada mahasiswa magang yang aktif.')"
            action="Pantau mahasiswa" :href="route('pantau.index')" />

    <div class="stat-grid">
        <x-stat label="Mahasiswa magang" :value="$stats['students']" unit="orang" :href="route('pantau.index')" hint="Yang sedang berjalan" />
        <x-stat label="Silabus menunggu" :value="$stats['syllabus']" unit="mahasiswa"
                :tone="$stats['syllabus'] > 0 ? 'amber' : 'green'"
                :hint="$stats['syllabus'] > 0 ? 'Perlu persetujuan Anda' : 'Tidak ada yang menunggu'" />
        <x-stat label="Jam minggu ini" :value="\App\Support\Num::short($stats['week_hours'], 1)" unit="jam" tone="gray" hint="Semua mahasiswa" />
        <x-stat label="Belum dinilai" :value="$stats['to_assess']" unit="mahasiswa" :href="route('penilaian.index')"
                :tone="$stats['to_assess'] > 0 ? 'blue' : 'green'" hint="Penilaian akhir belum dikirim" />
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Mahasiswa magang</h2>
                        <p class="card-sub">Hanya memantau: task dan timesheet dibuat sendiri oleh mahasiswa</p>
                    </div>
                    <a href="{{ route('pantau.index') }}" class="btn btn-sm">Semua</a>
                </div>

                @if ($internships->isEmpty())
                    <div class="empty">
                        <strong>Belum ada mahasiswa magang</strong>
                        Mahasiswa muncul setelah TU Fakultas mengaktifkan Anda sebagai Company Supervisor.
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="table table-compact">
                            <thead>
                                <tr>
                                    <th style="width: 34%">Mahasiswa</th>
                                    <th style="width: 26%">Divisi · posisi</th>
                                    <th>Progres task</th>
                                    <th class="col-num">Jam</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($internships as $internship)
                                    <tr>
                                        <td class="col-fluid">
                                            <a href="{{ route('pantau.show', $internship) }}" class="row-link"><x-person :user="$internship->student" /></a>
                                            <x-academic :user="$internship->student" compact class="cell-sub truncate" />
                                        </td>
                                        <td class="col-fluid">
                                            <span class="truncate">{{ $internship->division?->name ?? '–' }}</span>
                                            <div class="cell-sub truncate">{{ $internship->position ?? 'Posisi belum diisi' }}</div>
                                        </td>
                                        <td>
                                            <x-progress :value="$internship->percent()" />
                                            <div class="cell-sub">{{ $internship->completed_tasks_count }} dari {{ $internship->tasks_count }} task</div>
                                        </td>
                                        <td class="col-num">
                                            <span class="cell-title">{{ \App\Support\Num::short((float) ($weekHours[$internship->id] ?? 0), 1) }}</span>
                                            <div class="cell-sub">total {{ \App\Support\Num::short($internship->loggedHours(), 1) }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="card-foot">Kolom Jam: jam minggu ini (Senin sampai hari ini), di bawahnya total sejak mulai magang.</p>
                @endif
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Kegiatan terbaru</h2>
                        <p class="card-sub">Catatan timesheet terakhir dari mahasiswa</p>
                    </div>
                </div>
                @if ($recent->isEmpty())
                    <p class="meeting-empty">Belum ada catatan kegiatan.</p>
                @else
                    <ul class="item-list">
                        @foreach ($recent as $entry)
                            <li class="item-row">
                                <x-avatar :user="$entry->internship->student" small />
                                <div class="grow" style="min-width: 0">
                                    <div class="cell-title truncate">{{ $entry->description }}</div>
                                    <div class="cell-sub truncate">
                                        {{ $entry->internship->student->name }} · {{ $entry->work_date->locale('id')->translatedFormat('j M') }}
                                        @if ($entry->task) · {{ $entry->task->key() }} @endif
                                    </div>
                                </div>
                                <span class="nowrap cell-title">{{ \App\Support\Num::short((float) $entry->hours, 1) }} jam</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Silabus menunggu</h2>
                        <p class="card-sub">Tujuan hard skill, soft skill, dan attitude</p>
                    </div>
                </div>
                @if ($syllabus->isEmpty())
                    <p class="meeting-empty">Tidak ada silabus yang menunggu.</p>
                @else
                    <ul class="item-list">
                        @foreach ($syllabus as $internship)
                            <li class="item-row">
                                <div class="grow"><x-person :user="$internship->student" /></div>
                                <a href="{{ route('pantau.show', [$internship, 'tab' => 'silabus']) }}" class="btn btn-sm btn-primary">Periksa</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Status task</h2>
                        <p class="card-sub">Semua task mahasiswa magang Anda</p>
                    </div>
                </div>
                <div class="card-body">
                    <x-donut caption="task" unit="task" empty="Belum ada task" :segments="[
                        ['label' => 'Selesai', 'value' => $statusCounts['completed'] ?? 0, 'color' => 'green'],
                        ['label' => 'Dikerjakan', 'value' => $statusCounts['in_progress'] ?? 0, 'color' => 'blue'],
                        ['label' => 'Belum mulai', 'value' => $statusCounts['todo'] ?? 0, 'color' => 'gray'],
                    ]" />
                </div>
            </section>

            @include('dashboard._meeting', ['internships' => $internships, 'groups' => $groups, 'withoutMeeting' => $withoutMeeting])
        </div>
    </div>
@endsection
