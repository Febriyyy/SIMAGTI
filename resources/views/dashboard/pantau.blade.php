@extends('layouts.app')

@section('title', 'Dashboard '.$user->roleLabel())

@section('content')
    @php
        [$heroAction, $heroHref] = match (true) {
            $user->hasRole('kaprodi') => ['Persetujuan KP', route('kaprodi.persetujuan')],
            $user->hasRole('dospem') && $waitingValidation->isNotEmpty() => ['Buka validasi', route('validasi.index')],
            $attention->isNotEmpty() => ['Lihat yang perlu perhatian', '#perlu-perhatian'],
            default => ['Pantau kegiatan', route('pantau.index')],
        };
        $heroSub = match (true) {
            $user->hasRole('dospem') && $waitingValidation->isNotEmpty() => $subtitle.' '.$waitingValidation->count().' penilaian akhir menunggu validasi Anda.',
            $user->hasRole('kaprodi') && $waitingApproval > 0 => $waitingApproval.' pengajuan KP menunggu persetujuan Anda.',
            $attention->isNotEmpty() => $subtitle.' '.$attention->count().' mahasiswa perlu perhatian.',
            default => $subtitle.' Semua mahasiswa aktif dalam kondisi baik.',
        };
    @endphp
    <x-hero :eyebrow="$user->roleLabel()" :title="\App\Support\Greeting::now().', '.$user->firstName()"
            :sub="$heroSub" :action="$heroAction" :href="$heroHref" />

    @php($rules = \App\Actions\Dashboard\BuildMonitoringDashboard::class)
    <div class="stat-grid">
        <x-stat label="Magang aktif" :value="$stats['active']" unit="mahasiswa" icon="users"
                :hint="'di '.$stats['companies'].' perusahaan'" :href="route('pantau.index')" />

        <x-stat label="Task selesai" :value="$stats['average'].'%'" icon="tasks" :percent="$stats['average']" />

        @if ($attention->isEmpty())
            <x-stat label="Perlu perhatian" value="0" icon="check" tone="green" hint="Semua mahasiswa aman" />
        @else
            <x-stat label="Perlu perhatian" :value="$attention->count()" unit="mahasiswa" icon="alert" tone="amber"
                    href="#perlu-perhatian" hint="Alasannya ada di daftar bawah" />
        @endif

        <x-stat label="Nilai rata-rata" :value="$stats['average_score'] !== null ? \App\Support\Num::short($stats['average_score']) : null"
                icon="award" empty="Belum ada nilai"
                :hint="$assessment['validated'] > 0 ? 'dari '.$assessment['validated'].' penilaian tervalidasi' : 'Menunggu validasi pertama'" />

    </div>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Mahasiswa kerja praktik</h2>
                        <p class="card-sub">Urut dari yang sedang magang, lalu yang paling dekat selesai</p>
                    </div>
                </div>

                @if ($internships->isEmpty())
                    <div class="empty">
                        <strong>Belum ada data</strong>
                        Data akan muncul setelah ada penempatan magang.
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="table dt">
                            <thead>
                                <tr>
                                    <th class="w-person">Mahasiswa</th>
                                    <th>Tempat</th>
                                    <th class="w-progress">Progres</th>
                                    <th class="w-grade hide-md">Nilai</th>
                                    <th class="w-status">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($internships as $internship)
                                    @php($a = $internship->assessment)
                                    @php($student = $internship->student)
                                    <tr>
                                        <td>
                                            <a href="{{ route('pantau.show', $internship) }}" class="ident">
                                                <x-avatar :user="$student" />
                                                <span>
                                                    <span class="c-main" title="{{ $student->name }}">{{ $student->listName() }}</span>
                                                    <span class="c-sub">{{ $student->nim }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="c-main" title="{{ $internship->placeName() }}">{{ $internship->placeName() }}</span>
                                            <span class="c-sub" title="{{ $internship->placeDetail() }}">{{ $internship->position ?? 'Posisi belum diisi' }}</span>
                                        </td>
                                        <td>
                                            @if ($internship->status === 'diajukan')
                                                <span class="c-plain">Belum mulai</span>
                                            @else
                                                <x-progress :value="$internship->percent()" />
                                                <span class="c-sub" @if ($internship->last_activity_at) title="{{ $internship->last_activity_at->locale('id')->translatedFormat('j F Y, H:i') }}" @endif>
                                                    {{ $internship->last_activity_at ? 'Aktif '.mb_strtolower(\App\Support\Relative::label($internship->last_activity_at)) : 'Belum ada kegiatan' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="hide-md">
                                            @if ($a?->isValidated())
                                                <span class="c-main">{{ $a->letter_grade }} <span class="muted">· {{ \App\Support\Num::short($a->final_score) }}</span></span>
                                            @elseif ($a)
                                                <x-chip :tone="$a->statusTone()">{{ $a->status === 'submitted' ? 'Menunggu' : $a->statusLabel() }}</x-chip>
                                            @else
                                                <span class="muted">–</span>
                                            @endif
                                        </td>
                                        <td><x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

        <div class="split">
            <div class="col">
                @if ($user->hasRole('dospem'))
                    <section class="card">
                        <div class="card-head">
                            <div>
                                <h2 class="card-title">Menunggu validasi</h2>
                                <p class="card-sub">Penilaian akhir dari Company Supervisor</p>
                            </div>
                            <a href="{{ route('validasi.index') }}" class="btn btn-sm">Semua</a>
                        </div>
                        @if ($waitingValidation->isEmpty())
                            <p class="meeting-empty">Tidak ada yang menunggu.</p>
                        @else
                            <ul class="item-list">
                                @foreach ($waitingValidation as $internship)
                                    <li class="item-row">
                                        <div class="grow"><x-person :user="$internship->student" /></div>
                                        <a href="{{ route('validasi.show', $internship) }}" class="btn btn-sm btn-primary">Periksa</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endif

                <section class="card" id="perlu-perhatian">
                    <div class="card-head">
                        <h2 class="card-title">Perlu perhatian</h2>
                        @if ($attention->isEmpty())
                            <x-chip tone="green">Aman</x-chip>
                        @else
                            <x-chip tone="amber">{{ $attention->count() }} mahasiswa</x-chip>
                        @endif
                    </div>

                    @if ($attention->isEmpty())
                        <div class="calm">
                            <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
                            <p>Semua mahasiswa aktif mencatat kegiatan dan progresnya baik.</p>
                        </div>
                    @else
                        <ul class="watch-list">
                            @foreach ($attention->take(5) as $row)
                                @php($i = $row['internship'])
                                <li class="watch">
                                    <x-avatar :user="$i->student" small />
                                    <div class="watch-body">
                                        <div class="watch-name" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                                        <div class="watch-meta">{{ $i->student->nim }} · {{ $i->placeName() }}</div>
                                        <div class="watch-flags">
                                            @foreach ($row['reasons'] as $reason)
                                                <span class="watch-flag"><x-ikon name="alert" :size="12" /> {{ $reason }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <p class="card-foot">
                        Ditandai otomatis jika tidak ada kegiatan {{ $rules::IDLE_DAYS }} hari, progres di bawah {{ $rules::LOW_PROGRESS }}%,
                        atau KP tinggal {{ $rules::ENDING_DAYS }} hari tapi nilai belum divalidasi.
                    </p>
                </section>

                @include('dashboard._activity')
            </div>

            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Penilaian akhir</h2>
                            <p class="card-sub">Status penilaian mahasiswa yang magang</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <x-donut caption="mahasiswa" unit="mahasiswa" :segments="[
                            ['label' => 'Tervalidasi', 'value' => $assessment['validated'], 'color' => 'green'],
                            ['label' => 'Menunggu validasi', 'value' => $assessment['submitted'], 'color' => 'blue'],
                            ['label' => 'Draft supervisor', 'value' => $assessment['draft'], 'color' => 'amber'],
                            ['label' => 'Belum dinilai', 'value' => $assessment['none'], 'color' => 'gray'],
                        ]" />
                        <h3 class="label" style="margin: var(--space-5) 0 var(--space-2)">Sebaran huruf mutu</h3>
                        @include('dashboard._grades', ['grades' => $grades])
                    </div>
                </section>

            </div>
        </div>

        @if ($user->hasRole('kaprodi') && $companies->isNotEmpty())
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Per perusahaan</h2>
                        <p class="card-sub">Buka untuk melihat durasi, sebaran skill, dan performa</p>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Perusahaan</th>
                                <th>Magang aktif</th>
                                <th>Progres task</th>
                                <th>Nilai rata-rata</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $row)
                                <tr>
                                    <td class="cell-title">{{ $row['company']->name }}</td>
                                    <td>{{ $row['active'] }}</td>
                                    <td>
                                        @if ($row['average'] !== null)
                                            <x-progress :value="$row['average']" />
                                        @else
                                            <span class="muted">–</span>
                                        @endif
                                    </td>
                                    <td>{{ \App\Support\Num::short($row['score']) }}</td>
                                    <td class="col-action"><a href="{{ route('perusahaan.show', $row['company']) }}" class="btn btn-sm">Statistik</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
@endsection
