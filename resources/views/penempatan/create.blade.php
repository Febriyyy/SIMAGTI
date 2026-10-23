@extends('layouts.app')

@section('title', 'Tambah penempatan')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('penempatan.index') }}" class="back"><x-ikon name="back" :size="16" /> Penempatan</a>
            <h1 class="page-title">Tambah penempatan</h1>
            <p class="page-sub">Penempatan jalur kampus langsung berstatus berjalan. Company Supervisor harus staf dari perusahaan yang dipilih.</p>
        </div>
    </div>

    <section class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('penempatan.store') }}" class="form-grid">
                @csrf

                <div class="span-all">
                    <label for="student_id" class="label">Mahasiswa</label>
                    <select id="student_id" name="student_id" class="select" required>
                        <option value="">Pilih mahasiswa</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected((string) old('student_id') === (string) $student->id)>{{ $student->name }} ({{ $student->nim }})</option>
                        @endforeach
                    </select>
                    <p class="hint">Hanya mahasiswa yang belum punya penempatan aktif.</p>
                </div>

                <div>
                    <label for="company_id" class="label">Perusahaan</label>
                    <select id="company_id" name="company_id" class="select" required>
                        <option value="">Pilih perusahaan</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="division_id" class="label">Divisi (opsional)</label>
                    <select id="division_id" name="division_id" class="select">
                        <option value="">Tanpa divisi</option>
                        @foreach ($companies as $company)
                            @if ($company->divisions->isNotEmpty())
                                <optgroup label="{{ $company->name }}">
                                    @foreach ($company->divisions as $division)
                                        <option value="{{ $division->id }}" @selected((string) old('division_id') === (string) $division->id)>{{ $division->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="program_type" class="label">Jenis magang</label>
                    <select id="program_type" name="program_type" class="select" required>
                        @foreach (\App\Models\Internship::PROGRAMS['magang'][1] as $key => $label)
                            <option value="{{ $key }}" @selected(old('program_type', 'industri') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="position" class="label">Posisi (opsional)</label>
                    <input id="position" name="position" type="text" class="input" value="{{ old('position') }}" maxlength="100" placeholder="Misalnya Backend Developer Intern">
                </div>

                <div class="span-all">
                    <label for="supervisor_id" class="label">Company Supervisor</label>
                    <select id="supervisor_id" name="supervisor_id" class="select" required>
                        <option value="">Pilih Company Supervisor</option>
                        @foreach ($supervisors as $profile)
                            <option value="{{ $profile->user_id }}" @selected((string) old('supervisor_id') === (string) $profile->user_id)>{{ $profile->user->name }} · {{ $profile->company->name }}</option>
                        @endforeach
                    </select>
                    <p class="hint">Belum ada akunnya? Minta TU Fakultas mengundang supervisor terlebih dahulu.</p>
                </div>

                <div class="span-all">
                    <label for="lecturer_id" class="label">Dosen pembimbing</label>
                    <select id="lecturer_id" name="lecturer_id" class="select" required>
                        <option value="">Pilih dosen pembimbing</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}" @selected((string) old('lecturer_id') === (string) $lecturer->id)>{{ $lecturer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_date" class="label">Tanggal mulai</label>
                    <input id="start_date" name="start_date" type="date" class="input" value="{{ old('start_date') }}" required>
                </div>
                <div>
                    <label for="end_date" class="label">Tanggal selesai</label>
                    <input id="end_date" name="end_date" type="date" class="input" value="{{ old('end_date') }}" required>
                </div>

                <div class="span-all">
                    <button type="submit" class="btn btn-primary">Simpan penempatan</button>
                </div>
            </form>
        </div>
    </section>
@endsection
