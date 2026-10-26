@extends('layouts.app')

@section('title', 'Dashboard Company Supervisor')

@section('content')
    <x-hero eyebrow="Company Supervisor"
            :title="\App\Support\Greeting::now().', '.$user->firstName()"
            :sub="$stats['unconfirmed'] > 0
                ? $stats['unconfirmed'].' penempatan mahasiswa menunggu konfirmasi Anda.'
                : ($stats['students'] > 0 ? 'Pantau rencana, task, dan catatan kegiatan mahasiswa magang Anda.' : 'Belum ada mahasiswa magang yang aktif.')"
            action="Pantau mahasiswa" :href="route('pantau.index')" />

    <div class="stat-grid">
        <x-stat label="Mahasiswa magang" :value="$stats['students']" unit="orang" :href="route('pantau.index')" hint="Yang sedang berjalan" />
        <x-stat label="Perlu konfirmasi" :value="$stats['unconfirmed']" unit="mahasiswa"
                :tone="$stats['unconfirmed'] > 0 ? 'amber' : 'green'" :href="$stats['unconfirmed'] > 0 ? '#konfirmasi' : null"
                :hint="$stats['unconfirmed'] > 0 ? 'Data penempatan' : 'Semua sudah dikonfirmasi'" />
        <x-stat label="Catatan minggu ini" :value="$stats['week_notes']" unit="catatan" tone="gray" :href="route('aktivitas.index')" hint="Semua mahasiswa" />
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
                        <p class="card-sub">Rencana, task, dan catatan diisi mahasiswa</p>
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
                            <x-intern-row :internship="$internship" :week="$weekNotes[$internship->id] ?? 0" compact />
                        @endforeach
                    </x-intern-list>
                    <p class="card-foot">Catatan minggu ini: Senin sampai hari ini.</p>
                @endif
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Kegiatan terbaru</h2>
                        <p class="card-sub">Catatan timesheet terakhir dari mahasiswa</p>
                    </div>
                    <a href="{{ route('aktivitas.index') }}" class="btn btn-sm">Aktivitas</a>
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
