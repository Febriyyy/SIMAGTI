@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Log Aktivitas</h1>
            <p class="page-sub">50 aktivitas terakhir di sistem: siapa mengubah apa dan kapan.</p>
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
                                <td class="muted" style="white-space: nowrap">{{ $log->created_at->locale('id')->translatedFormat('j M Y, H:i') }}</td>
                                <td>
                                    @if ($log->user)
                                        <div class="person">
                                            <span class="person-name">{{ $log->user->name }}</span>
                                            <span class="person-sub">{{ $log->user->roleLabel() }}</span>
                                        </div>
                                    @else
                                        <span class="muted">–</span>
                                    @endif
                                </td>
                                <td><x-chip>{{ $log->action }}</x-chip></td>
                                <td>{{ $log->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
