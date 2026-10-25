@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Log Aktivitas</h1>
            <p class="page-sub">50 aktivitas terakhir.</p>
        </div>
    </div>

    <section class="card">
        @if ($logs->isEmpty())
            <div class="empty">
                <strong>Belum ada aktivitas</strong>
                Aktivitas akan tercatat otomatis saat pengguna memakai sistem.
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aksi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="muted">{{ $log->created_at->locale('id')->translatedFormat('j M Y, H:i') }}</td>
                                <td>
                                    @if ($log->user)
                                        <div class="person">
                                            <x-avatar :user="$log->user" small />
                                            <div class="person-text">
                                                <span class="person-name" title="{{ $log->user->name }}">{{ $log->user->listName() }}</span>
                                                <span class="person-sub">{{ $log->user->roleLabel() }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="muted">–</span>
                                    @endif
                                </td>
                                <td><x-chip>{{ $log->action }}</x-chip></td>
                                <td class="cell-wrap">{{ $log->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
