@extends('layouts.app')

@section('title', 'Task Mingguan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Task Mingguan</h1>
            <p class="page-sub">
                @if ($internship)
                    {{ $internship->company->name }}
                @else
                    Daftar task dari mentor.
                @endif
            </p>
        </div>
    </div>

    @if (! $internship)
        <div class="card">
            <div class="empty">
                <strong>Belum ada penempatan magang</strong>
                Task akan muncul setelah penempatan magangmu aktif.
            </div>
        </div>
    @else
        <div class="toolbar">
            <div class="week-nav">
                <a href="{{ route('tugas.index', ['minggu' => $week - 1]) }}" class="btn btn-sm {{ $week <= 1 ? 'is-disabled' : '' }}" @if ($week <= 1) aria-disabled="true" tabindex="-1" @endif>‹ Sebelumnya</a>
                <span class="week-label">Minggu ke-{{ $week }} <span class="muted">dari {{ $internship->totalWeeks() }}</span></span>
                <a href="{{ route('tugas.index', ['minggu' => $week + 1]) }}" class="btn btn-sm {{ $week >= $internship->totalWeeks() ? 'is-disabled' : '' }}" @if ($week >= $internship->totalWeeks()) aria-disabled="true" tabindex="-1" @endif>Berikutnya ›</a>
            </div>
        </div>

        <section class="card">
            @if ($tasks->isEmpty())
                <div class="empty">
                    <strong>Belum ada task di minggu ini</strong>
                    Mentor akan menambahkannya.
                </div>
            @else
                <x-task-table :tasks="$tasks" />
            @endif
        </section>
    @endif
@endsection
