@extends('layouts.app')

@section('title', 'Penempatan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Penempatan KP</h1>
            <p class="page-sub">Semua mahasiswa dan tempat KP-nya.</p>
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
                <table class="table dt">
                    <thead>
                        <tr>
                            <th class="w-person">Mahasiswa</th>
                            <th>Tempat</th>
                            <th class="w-name hide-md">Dosen pembimbing</th>
                            <th class="w-progress">Task selesai</th>
                            <th class="w-status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($internships as $internship)
                            @php($student = $internship->student)
                            <tr>
                                <td>
                                    <span class="ident">
                                        <x-avatar :user="$student" />
                                        <span>
                                            <span class="c-main" title="{{ $student->name }}">{{ $student->listName() }}</span>
                                            <span class="c-sub">{{ $student->nim }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <span class="c-main" title="{{ $internship->placeName() }}">{{ $internship->placeName() }}</span>
                                    <span class="c-sub" title="Company Supervisor: {{ $internship->supervisor?->name ?? 'belum aktif' }}">{{ $internship->supervisor ? 'Supervisor '.$internship->supervisor->listName() : 'Supervisor belum aktif' }}</span>
                                </td>
                                <td class="hide-md">
                                    <span class="c-main c-plain" title="{{ $internship->lecturer?->name }}">{{ $internship->lecturer?->listName() ?? '–' }}</span>
                                    <span class="c-sub">{{ $internship->track === 'mandiri' ? 'Pengajuan mandiri' : 'Penempatan kampus' }}</span>
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
