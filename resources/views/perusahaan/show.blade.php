@extends('layouts.app')

@section('title', $company->name)

@section('content')
    @php($perf = $stats['performance'])
    <div class="page-head">
        <div>
            <a href="{{ route('perusahaan.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke perusahaan</a>
            <h1 class="page-title">{{ $company->name }}</h1>
            <p class="page-sub">{{ $company->industry ?? 'Bidang belum diisi' }}@if ($company->city) · {{ $company->city }}@endif</p>
        </div>
        <x-chip :tone="$company->statusTone()">{{ $company->statusLabel() }}</x-chip>
    </div>

    <div class="stat-grid">
        <x-stat label="Sedang magang" :value="$stats['ongoing_count']" unit="mahasiswa" icon="users"
                :hint="$stats['finished_count'].' sudah selesai'" />
        <x-stat label="Rata-rata durasi" :value="\App\Support\Num::short($stats['average_duration_months'], 1)" unit="bulan" icon="calendar"
                hint="Semua penempatan" />
        <x-stat label="Task selesai" :value="$stats['average_progress_percent'].'%'" icon="tasks" :percent="$stats['average_progress_percent']" />
        <x-stat label="Nilai rata-rata" :value="$perf['average_final_score'] !== null ? \App\Support\Num::short($perf['average_final_score']) : null"
                icon="award" empty="Belum ada nilai"
                :hint="$perf['assessed_count'] > 0 ? $perf['assessed_count'].' tervalidasi' : 'Menunggu validasi'" />
    </div>

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Mahasiswa</h2>
                <p class="card-sub">Yang sedang dan pernah magang di sini</p>
            </div>
        </div>
        @if (empty($stats['students']))
            <div class="empty">
                <strong>Belum ada mahasiswa</strong>
                Data muncul setelah ada penempatan yang berjalan.
            </div>
        @else
            <div class="table-wrap">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th class="num">Durasi</th>
                            <th>Progres task</th>
                            <th class="num">Nilai akhir</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['students'] as $row)
                            @php($status = \App\Models\Internship::STATUSES[$row['status']] ?? [$row['status'], 'gray'])
                            <tr>
                                <td>
                                    <div class="person">
                                        <x-avatar :user="(object) ['name' => $row['name']]" small />
                                        <div class="person-text">
                                            <span class="person-name">{{ $row['name'] }}</span>
                                            <span class="person-sub">{{ $row['nim'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="num">
                                    <span class="cell-title">{{ $row['duration_months'] }} bulan</span>
                                    @if ($row['status'] === 'berjalan')
                                        <span class="muted">· sisa {{ $row['days_left'] }} hari</span>
                                    @endif
                                </td>
                                <td><x-progress :value="$row['progress_percent']" inside /></td>
                                <td class="num">
                                    @if ($row['letter_grade'])
                                        <span class="cell-title">{{ $row['letter_grade'] }}</span>
                                        <span class="muted">· {{ \App\Support\Num::short($row['final_score']) }}</span>
                                    @else
                                        <span class="muted" title="Belum divalidasi ketua divisi">–</span>
                                    @endif
                                </td>
                                <td><x-chip :tone="$status[1]">{{ $status[0] }}</x-chip></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="split">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Sebaran skill</h2>
                        <p class="card-sub">Persentase mahasiswa yang sedang magang</p>
                    </div>
                </div>
                <div class="card-body">
                    @if (empty($stats['skills']))
                        <p class="muted">Belum ada mahasiswa yang sedang magang.</p>
                    @else
                        <ul class="hbars">
                            @foreach ($stats['skills'] as $skill)
                                <li>
                                    <div class="hbar-head">
                                        <span class="hbar-name">{{ $skill['name'] }}</span>
                                        <span class="hbar-value">{{ $skill['percent'] }}% · {{ $skill['students'] }} orang</span>
                                    </div>
                                    <div class="hbar-track"><span class="hbar-fill" style="width: {{ $skill['percent'] }}%"></span></div>
                                </li>
                            @endforeach
                        </ul>
                        <p class="hint" style="margin-top: var(--space-3)">Satu mahasiswa bisa punya beberapa skill, jadi totalnya bisa lebih dari 100%.</p>
                    @endif
                </div>
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Performa per kategori</h2>
                        <p class="card-sub">Rata-rata hard skill, soft skill, dan attitude dari penilaian yang sudah divalidasi</p>
                    </div>
                </div>
                <div class="card-body">
                    @if (empty($perf['aspects']))
                        <p class="muted">Belum ada penilaian yang divalidasi di perusahaan ini.</p>
                    @else
                        <ul class="hbars">
                            @foreach ($perf['aspects'] as $aspect)
                                <li>
                                    <div class="hbar-head">
                                        <span class="hbar-name">{{ $aspect['name'] }}</span>
                                        <span class="hbar-value">{{ \App\Support\Num::short($aspect['average']) }}</span>
                                    </div>
                                    <div class="hbar-track"><span class="hbar-fill" style="width: {{ min(100, $aspect['average']) }}%"></span></div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Sebaran huruf mutu</h2>
                </div>
                <div class="card-body">
                    @include('dashboard._grades', ['grades' => $perf['grades']])
                </div>
            </section>
        </div>
    </div>
    <section class="card" id="ulasan">
        <div class="card-head">
            <div>
                <h2 class="card-title">Ulasan mahasiswa</h2>
                <p class="card-sub">{{ $reviewSummary['count'] ? $reviewSummary['count'].' ulasan · '.$reviewSummary['recommend'].'% merekomendasikan' : 'Diisi mahasiswa setelah masa magang berakhir' }}</p>
            </div>
            @if ($reviewSummary['count'])
                <span class="rv-score"><b>{{ \App\Support\Num::short($reviewSummary['rating'], 1) }}</b><x-stars :value="$reviewSummary['rating']" :size="16" /></span>
            @endif
        </div>
        @if ($reviews->isEmpty())
            <div class="empty"><strong>Belum ada ulasan</strong> Mahasiswa bisa mengulas tempat magang dari halaman Status KP setelah magang berakhir.</div>
        @else
            <div class="card-body rv-company">
                <dl class="rv-bars">
                    @foreach (\App\Models\CompanyReview::ASPECTS as $key => [$label])
                        <div><dt>{{ $label }}</dt><dd><span class="rv-bar"><i style="width: {{ $reviewSummary['aspects'][$key] / 5 * 100 }}%"></i></span><b>{{ \App\Support\Num::short($reviewSummary['aspects'][$key], 1) }}</b></dd></div>
                    @endforeach
                </dl>
                <ul class="rv-list">
                    @foreach ($reviews as $r)
                        <li>
                            <x-avatar :user="$r->student" small />
                            <div class="min0">
                                <p class="rv-list-head"><b>{{ $r->student?->listName() }}</b><x-stars :value="$r->rating" /><span>{{ $r->created_at->locale('id')->translatedFormat('j M Y') }}</span>@unless ($r->recommend)<span class="rv-norec">Tidak merekomendasikan</span>@endunless</p>
                                @if ($r->comment)<p class="rv-list-body">{{ $r->comment }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
@endsection
