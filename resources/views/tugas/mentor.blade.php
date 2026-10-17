@extends('layouts.app')

@section('title', 'Task Mingguan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Task Mingguan</h1>
            <p class="page-sub">Beri tugas dan tinjau pekerjaan mahasiswa bimbingan.</p>
        </div>
    </div>

    @if (! $selected)
        <div class="card">
            <div class="empty">
                <strong>Belum ada mahasiswa bimbingan</strong>
                Mahasiswa akan muncul setelah penempatan magangnya aktif.
            </div>
        </div>
    @else
        <div class="toolbar">
            <form method="GET" action="{{ route('mentor.tugas') }}" class="inline-form">
                <label for="magang" class="label" style="margin: 0">Mahasiswa</label>
                <select id="magang" name="magang" class="select select-sm">
                    @foreach ($internships as $item)
                        <option value="{{ $item->id }}" @selected($item->id === $selected->id)>{{ $item->student->name }} ({{ $item->student->nim }})</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm">Tampilkan</button>
            </form>

            <div class="week-nav">
                <a href="{{ route('mentor.tugas', ['magang' => $selected->id, 'minggu' => $week - 1]) }}" class="btn btn-sm {{ $week <= 1 ? 'is-disabled' : '' }}" @if ($week <= 1) aria-disabled="true" tabindex="-1" @endif>‹ Sebelumnya</a>
                <span class="week-label">Minggu ke-{{ $week }} <span class="muted">dari {{ $selected->totalWeeks() }}</span></span>
                <a href="{{ route('mentor.tugas', ['magang' => $selected->id, 'minggu' => $week + 1]) }}" class="btn btn-sm {{ $week >= $selected->totalWeeks() ? 'is-disabled' : '' }}" @if ($week >= $selected->totalWeeks()) aria-disabled="true" tabindex="-1" @endif>Berikutnya ›</a>
            </div>
        </div>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Task minggu ke-{{ $week }}</h2>
                    <p class="card-sub">{{ $selected->student->name }} ({{ $selected->student->nim }})</p>
                </div>
            </div>

            @if ($tasks->isEmpty())
                <div class="empty">
                    <strong>Belum ada task di minggu ini</strong>
                    Tambahkan task lewat formulir di bawah.
                </div>
            @else
                <x-task-table :tasks="$tasks" action="Tinjau" />
            @endif
        </section>

        <details class="card" @if ($errors->hasAny(['day', 'title', 'description', 'minggu'])) open @endif>
            <summary>Tambah task</summary>
            <div class="card-body">
                <form method="POST" action="{{ route('mentor.tugas.store') }}" class="form-grid">
                    @csrf
                    <input type="hidden" name="magang" value="{{ $selected->id }}">
                    <input type="hidden" name="minggu" value="{{ $week }}">

                    <div>
                        <label for="day" class="label">Hari</label>
                        <select id="day" name="day" class="select">
                            @foreach (\App\Models\WeeklyTask::DAYS as $key => $label)
                                <option value="{{ $key }}" @selected(old('day') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="title" class="label">Judul task</label>
                        <input id="title" name="title" type="text" class="input" value="{{ old('title') }}" maxlength="150" required>
                    </div>

                    <div class="span-all">
                        <label for="description" class="label">Deskripsi (opsional)</label>
                        <textarea id="description" name="description" class="textarea" maxlength="1000">{{ old('description') }}</textarea>
                    </div>

                    <div class="span-all">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="plus" :size="16" /> Tambah task
                        </button>
                    </div>
                </form>
            </div>
        </details>
    @endif
@endsection
