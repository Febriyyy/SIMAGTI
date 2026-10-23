@extends('layouts.app')

@section('title', 'Penempatan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Penempatan KP</h1>
            <p class="page-sub">Seluruh mahasiswa beserta tempat, Company Supervisor, dan dosen pembimbingnya.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('penempatan.create') }}" class="btn btn-primary"><x-ikon name="plus" :size="16" /> Tambah penempatan</a>
        @endif
    </div>

    <form method="GET" action="{{ route('penempatan.index') }}" class="inline-form">
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
                            <th style="width: 26%">Mahasiswa</th>
                            <th style="width: 26%">Tempat</th>
                            <th>Dospem · jalur</th>
                            <th>Task selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($internships as $internship)
                            <tr>
                                <td class="col-fluid"><x-person :user="$internship->student" /></td>
                                <td class="col-fluid">
                                    <div class="cell-title truncate" title="{{ $internship->placeName() }}">{{ $internship->placeName() }}</div>
                                    @if ($internship->isSeminar())
                                        <div class="cell-sub truncate">{{ $internship->programTypeLabel() }}</div>
                                    @else
                                        <div class="cell-sub truncate" title="Company Supervisor: {{ $internship->supervisor?->name ?? 'belum aktif' }}">Supervisor: {{ $internship->supervisor?->name ?? 'belum aktif' }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span title="{{ $internship->lecturer?->name }}">{{ $internship->lecturer?->shortName() ?? '–' }}</span>
                                    <div class="cell-sub">{{ $internship->programTypeLabel() }} · {{ $internship->track === 'mandiri' ? 'mandiri' : 'kampus' }}</div>
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
@endsection
