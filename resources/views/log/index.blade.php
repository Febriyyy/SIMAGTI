@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Log Aktivitas</h1>
            <p class="page-sub">Semua aktivitas pengguna, terbaru di atas.</p>
        </div>
    </div>

    <form method="GET" class="filters" data-autosubmit>
        <div class="filter-search">
            <x-ikon name="search" :size="16" />
            <input type="search" name="cari" value="{{ $term }}" placeholder="Cari keterangan atau nama…" aria-label="Cari aktivitas" class="input input-sm">
        </div>
        <select name="peran" class="select select-sm" aria-label="Peran">
            <option value="">Semua peran</option>
            @foreach (\App\Models\User::ROLE_LABELS as $key => $label)
                <option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <section class="card">
        @if ($logs->isEmpty())
            <div class="empty">
                <strong>Belum ada aktivitas</strong>
                Aktivitas tercatat otomatis saat pengguna memakai sistem.
            </div>
        @else
            <div class="table-wrap">
                <table class="table dt">
                    <thead>
                        <tr>
                            <th class="w-person">Pengguna</th>
                            <th>Aktivitas</th>
                            <th class="w-date">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>
                                    @if ($log->user)
                                        <span class="ident">
                                            <x-avatar :user="$log->user" small />
                                            <span>
                                                <span class="c-main" title="{{ $log->user->name }}">{{ $log->user->listName() }}</span>
                                                <span class="c-sub">{{ $log->user->roleLabel() }}</span>
                                            </span>
                                        </span>
                                    @else
                                        <span class="muted">Sistem</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="c-main c-plain" title="{{ $log->description }}">{{ $log->description }}</span>
                                    <span class="c-sub">{{ $log->action }}</span>
                                </td>
                                <td>
                                    <span class="c-main c-plain">{{ $log->created_at->locale('id')->translatedFormat('j M Y') }}</span>
                                    <span class="c-sub">{{ $log->created_at->format('H:i') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="card-foot pager">
                    <span>{{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ $logs->total() }} aktivitas</span>
                    <span class="row-actions">
                        @if ($logs->previousPageUrl())<a href="{{ $logs->previousPageUrl() }}" class="btn btn-sm">Sebelumnya</a>@endif
                        @if ($logs->nextPageUrl())<a href="{{ $logs->nextPageUrl() }}" class="btn btn-sm">Berikutnya</a>@endif
                    </span>
                </div>
            @endif
        @endif
    </section>
@endsection
