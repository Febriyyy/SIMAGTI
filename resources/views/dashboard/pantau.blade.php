@extends('layouts.app')

@section('title', 'Dashboard '.$user->roleLabel())

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ \App\Support\Greeting::now() }}, {{ $user->firstName() }}</h1>
            <p class="page-sub">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="pills">
        <span class="pill"><x-icon name="users" :size="16" /> Magang aktif <strong>{{ $stats['active'] }}</strong></span>
        <span class="pill"><x-icon name="building" :size="16" /> Perusahaan <strong>{{ $stats['companies'] }}</strong></span>
        <span class="pill"><x-icon name="tasks" :size="16" /> Rata-rata task selesai <strong>{{ $stats['average'] }}%</strong></span>
        <span class="pill"><x-icon name="calendar" :size="16" /> Selesai magang <strong>{{ $stats['finished'] }}</strong></span>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Mahasiswa kerja praktik</h2>
                </div>

                @if ($internships->isEmpty())
                    <div class="empty">
                        <strong>Belum ada data</strong>
                        Data akan muncul setelah ada penempatan magang.
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mahasiswa</th>
                                    <th>Perusahaan</th>
                                    <th>Progres task</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($internships as $internship)
                                    <tr>
                                        <td><x-person :user="$internship->student" /></td>
                                        <td>
                                            {{ $internship->company->name }}
                                            <div class="cell-sub">Mentor: {{ $internship->mentor?->name ?? '–' }}</div>
                                        </td>
                                        <td><x-progress :value="$internship->percent()" /></td>
                                        <td><x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Perlu perhatian</h2>
                        <p class="card-sub">Progres task paling rendah</p>
                    </div>
                </div>
                @if ($attention->isEmpty())
                    <p class="meeting-empty">Belum ada mahasiswa yang magang aktif.</p>
                @else
                    <ul class="item-list">
                        @foreach ($attention as $internship)
                            <li>
                                <x-person :user="$internship->student" />
                                <div style="margin-top: 8px"><x-progress :value="$internship->percent()" /></div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
