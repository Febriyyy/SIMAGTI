@extends('layouts.app')

@section('title', 'Mahasiswa')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Mahasiswa</h1>
            <p class="page-sub">Akun mahasiswa dan status KP masing-masing.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('mahasiswa.index') }}" class="inline-form">
        <input type="search" name="q" class="input" value="{{ $search }}" placeholder="Cari nama atau NIM" aria-label="Cari nama atau NIM">
        <button type="submit" class="btn">Cari</button>
    </form>

    <section class="card">
        @if ($students->isEmpty())
            <div class="empty">
                <strong>Mahasiswa tidak ditemukan</strong>
                Coba kata kunci lain atau tambahkan mahasiswa lewat formulir di bawah.
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Program studi</th>
                            <th>Angkatan</th>
                            <th>Status KP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php($latest = $student->internships->first())
                            <tr>
                                <td><x-person :user="$student" /></td>
                                <td>{{ $student->studentProfile?->studyProgram?->name ?? '–' }}</td>
                                <td>{{ $student->studentProfile?->entry_year ?? '–' }}</td>
                                <td>
                                    @if ($latest)
                                        <x-chip :tone="$latest->statusTone()">{{ $latest->statusLabel() }}</x-chip>
                                        <div class="cell-sub">{{ $latest->company->name }}</div>
                                    @else
                                        <span class="muted">Belum ada</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <details class="card" @if ($errors->hasAny(['name', 'email', 'nim', 'study_program_id', 'entry_year', 'gpa', 'password'])) open @endif>
        <summary>Tambah mahasiswa</summary>
        <div class="card-body">
            <form method="POST" action="{{ route('mahasiswa.store') }}" class="form-grid">
                @csrf

                <div>
                    <label for="name" class="label">Nama lengkap</label>
                    <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" maxlength="100" required>
                </div>
                <div>
                    <label for="nim" class="label">NIM</label>
                    <input id="nim" name="nim" type="text" class="input" value="{{ old('nim') }}" maxlength="20" required>
                </div>
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" maxlength="150" required>
                </div>
                <div>
                    <label for="study_program_id" class="label">Program studi</label>
                    <select id="study_program_id" name="study_program_id" class="select" required>
                        <option value="">Pilih program studi</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) old('study_program_id') === (string) $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="entry_year" class="label">Angkatan</label>
                    <input id="entry_year" name="entry_year" type="number" class="input" value="{{ old('entry_year') }}" min="2000" max="{{ date('Y') + 1 }}">
                </div>
                <div>
                    <label for="gpa" class="label">IPK (opsional)</label>
                    <input id="gpa" name="gpa" type="number" class="input" value="{{ old('gpa') }}" min="0" max="4" step="0.01">
                </div>
                <div class="span-all">
                    <label for="password" class="label">Kata sandi awal</label>
                    <input id="password" name="password" type="text" class="input" minlength="8" required autocomplete="off">
                    <p class="hint">Minimal 8 karakter. Sampaikan ke mahasiswa agar diganti setelah masuk.</p>
                </div>
                <div class="span-all">
                    <button type="submit" class="btn btn-primary"><x-icon name="plus" :size="16" /> Tambah mahasiswa</button>
                </div>
            </form>
        </div>
    </details>
@endsection
