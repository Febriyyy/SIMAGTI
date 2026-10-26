@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <x-hero eyebrow="Admin"
            :title="\App\Support\Greeting::now().', '.$user->firstName()"
            :sub="$stats['inactive'] > 0
                ? $stats['inactive'].' akun sedang nonaktif. Kelola peran dan status akun dari menu Akun.'
                : 'Semua akun aktif. Kelola peran, standar penilaian, dan log aktivitas dari sini.'"
            action="Kelola akun" :href="route('admin.akun')" />

    <div class="stat-grid">
        <x-stat label="Total akun" :value="$stats['users']" unit="akun" :href="route('admin.akun')" hint="Semua peran" />
        <x-stat label="Akun nonaktif" :value="$stats['inactive']" unit="akun" :tone="$stats['inactive'] > 0 ? 'amber' : 'gray'"
                :href="route('admin.akun', ['status' => 'nonaktif'])" hint="Tidak bisa masuk" />
        <x-stat label="Pertanyaan penilaian" :value="$stats['questions']" unit="butir" tone="gray" :href="route('admin.penilaian')" hint="Standar kampus" />
        <x-stat label="Aktivitas hari ini" :value="$stats['logs_today']" unit="catatan" tone="gray" :href="route('log.index')" hint="Seluruh pengguna" />
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Aktivitas terbaru</h2>
                        <p class="card-sub">Seluruh pengguna sistem</p>
                    </div>
                    <a href="{{ route('log.index') }}" class="btn btn-sm">Lihat semua</a>
                </div>
                @if ($logs->isEmpty())
                    <div class="empty"><strong>Belum ada aktivitas</strong> Aktivitas tercatat otomatis.</div>
                @else
                    <ul class="item-list">
                        @foreach ($logs as $log)
                            <li class="item-row">
                                @if ($log->user)<x-avatar :user="$log->user" small />@else<span class="item-icon"><x-ikon name="history" :size="18" /></span>@endif
                                <div class="grow" style="min-width: 0">
                                    <div class="cell-title truncate" title="{{ $log->description }}">{{ $log->description }}</div>
                                    <div class="cell-sub truncate">{{ $log->user?->listName() ?? 'Sistem' }} · {{ $log->created_at->locale('id')->diffForHumans() }}</div>
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
                    <h2 class="card-title">Akun per peran</h2>
                </div>
                <ul class="item-list">
                    @foreach (\App\Models\User::ROLE_LABELS as $key => $label)
                        <li class="item-row">
                            <a href="{{ route('admin.akun', ['peran' => $key]) }}" class="grow cell-title">{{ $label }}</a>
                            <span class="num">{{ $roles[$key] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
@endsection
