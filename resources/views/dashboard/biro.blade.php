@extends('layouts.app')

@section('title', 'Dashboard Biro Kerjasama')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ \App\Support\Greeting::now() }}, {{ $user->firstName() }}</h1>
            <p class="page-sub">Perusahaan mitra dan mahasiswa yang sedang magang.</p>
        </div>
    </div>

    <div class="pills">
        <span class="pill"><x-icon name="building" :size="16" /> Perusahaan mitra <strong>{{ $stats['partners'] }}</strong></span>
        <span class="pill"><x-icon name="clock" :size="16" /> Menunggu verifikasi <strong>{{ $stats['waiting'] }}</strong></span>
        <span class="pill"><x-icon name="users" :size="16" /> Mahasiswa magang aktif <strong>{{ $stats['students'] }}</strong></span>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Perusahaan</h2>
                </div>

                @if ($companies->isEmpty())
                    <div class="empty">
                        <strong>Belum ada perusahaan</strong>
                        Perusahaan akan muncul setelah ditambahkan.
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Perusahaan</th>
                                    <th>Bidang</th>
                                    <th>Status</th>
                                    <th>Mahasiswa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($companies as $company)
                                    <tr>
                                        <td>
                                            <div class="cell-title">{{ $company->name }}</div>
                                            @if ($company->address)
                                                <div class="cell-sub">{{ $company->address }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $company->industry ?? '–' }}</td>
                                        <td><x-chip :tone="$company->statusTone()">{{ $company->statusLabel() }}</x-chip></td>
                                        <td>{{ $company->internships_count }}</td>
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
                        <h2 class="card-title">Menunggu verifikasi</h2>
                        <p class="card-sub">Perusahaan dari jalur KP mandiri</p>
                    </div>
                    <a href="{{ route('biro.verifikasi') }}" class="btn btn-sm">Buka</a>
                </div>
                @if ($waiting->isEmpty())
                    <p class="meeting-empty">Tidak ada yang menunggu.</p>
                @else
                    <ul class="item-list">
                        @foreach ($waiting as $company)
                            <li>
                                <div class="cell-title">{{ $company->name }}</div>
                                <div class="cell-sub">{{ $company->industry ?? '–' }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
