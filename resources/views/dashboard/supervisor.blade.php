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

    @php($unconfirmed = $internships->whereNull('supervisor_confirmation'))
    @if ($unconfirmed->isNotEmpty())
        <section class="card callout" id="konfirmasi">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Konfirmasi penempatan</h2>
                    <p class="card-sub">Cukup sekali per mahasiswa</p>
                </div>
                <x-chip tone="amber">{{ $unconfirmed->count() }}</x-chip>
            </div>
            <div class="confirm-list">
                @foreach ($unconfirmed as $internship)
                    @include('pantau._confirm-item', ['internship' => $internship])
                @endforeach
            </div>
        </section>
    @endif

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Mahasiswa magang</h2>
                        <p class="card-sub">Task dan timesheet diisi mahasiswa</p>
                    </div>
                    <a href="{{ route('pantau.index') }}" class="btn btn-sm">Semua</a>
                </div>

                @if ($internships->isEmpty())
                    <div class="empty">
                        <strong>Belum ada mahasiswa magang</strong>
                        Mahasiswa muncul setelah TU Fakultas mengaktifkan Anda sebagai Company Supervisor.
                    </div>
                @else
                    <x-intern-list compact>
                        @foreach ($internships as $internship)
                            <x-intern-row :internship="$internship" :week="$weekHours[$internship->id] ?? 0" compact />
                        @endforeach
                    </x-intern-list>
                    <p class="card-foot">Jam minggu ini: Senin sampai hari ini.</p>
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
                                        {{ $entry->internship->student->listName() }} · {{ $entry->work_date->locale('id')->translatedFormat('j M') }}
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
