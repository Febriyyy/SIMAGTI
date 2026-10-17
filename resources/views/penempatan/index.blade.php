@extends('layouts.app')

@section('title', 'Penempatan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Penempatan KP</h1>
            <p class="page-sub">Seluruh mahasiswa beserta tempat, mentor, dan dosen pembimbingnya.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('penempatan.create') }}" class="btn btn-primary"><x-icon name="plus" :size="16" /> Tambah penempatan</a>
        @endif
    </div>

    <form method="GET" action="{{ route('penempatan.index') }}" class="inline-form" style="margin-bottom: 16px">
        <label for="status" class="label" style="margin: 0">Status</label>
        <select id="status" name="status" class="select select-sm">
            <option value="">Semua</option>
            @foreach (\App\Models\Internship::STATUSES as $key => [$label])
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm">Tampilkan</button>
    </form>

    <section class="card">
        @if ($internships->isEmpty())
            <div class="empty">
                <strong>Belum ada penempatan</strong>
                Penempatan akan muncul setelah dibuat atau disetujui.
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Perusahaan</th>
                            <th>Mentor</th>
                            <th>Dosen pembimbing</th>
                            <th>Jalur</th>
                            <th>Task selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($internships as $internship)
                            <tr>
                                <td><x-person :user="$internship->student" /></td>
                                <td>{{ $internship->company->name }}</td>
                                <td>{{ $internship->mentor?->name ?? '–' }}</td>
                                <td>{{ $internship->lecturer?->name ?? '–' }}</td>
                                <td>{{ $internship->track === 'mandiri' ? 'Mandiri' : 'Lowongan kampus' }}</td>
                                <td><x-progress :value="$internship->percent()" /></td>
                                <td><x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
