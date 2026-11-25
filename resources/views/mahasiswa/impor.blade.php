@extends('layouts.app')

@section('title', 'Impor Mahasiswa')

@section('content')
    <a href="{{ route('mahasiswa.index') }}" class="page-back"><x-ikon name="back" :size="16" /> Mahasiswa</a>
    <div class="page-head">
        <div>
            <h1 class="page-title">Impor mahasiswa</h1>
            <p class="page-sub">Tambah banyak mahasiswa sekaligus dari Excel atau CSV.</p>
        </div>
        <a href="{{ route('mahasiswa.impor.template') }}" class="btn"><x-ikon name="download" :size="16" /> Unduh template</a>
    </div>

    @if ($result)
        {{-- Hasil impor: unduh daftar kata sandi sekali --}}
        <section class="card callout" aria-live="polite">
            <div class="card-body pt stack">
                <div>
                    <h2 class="card-title">{{ count($result['created']) }} mahasiswa ditambahkan</h2>
                    @if ($result['skipped'])<p class="card-sub">{{ $result['skipped'] }} baris dilewati karena sudah terdaftar.</p>@endif
                </div>
                @if ($result['created'])
                    <p class="muted">Unduh daftar akun beserta kata sandi sementara, lalu bagikan ke mahasiswa. Daftar ini hanya bisa diunduh sekali.</p>
                    <div class="row-actions" style="justify-content: flex-start">
                        <a href="{{ route('mahasiswa.impor.akun') }}" class="btn btn-primary"><x-ikon name="download" :size="16" /> Unduh daftar akun</a>
                        <a href="{{ route('mahasiswa.index') }}" class="btn">Lihat mahasiswa</a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($check)
        {{-- Pratinjau: belum ada yang disimpan --}}
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Pratinjau</h2>
                    <p class="card-sub">{{ $check['file'] }}</p>
                </div>
                <div class="row-actions">
                    <span class="chip chip-green">{{ count($check['valid']) }} siap</span>
                    @if ($check['invalid'])<span class="chip chip-red">{{ count($check['invalid']) }} bermasalah</span>@endif
                </div>
            </div>

            {{-- Satu tabel: baris bermasalah di atas (pesan mengisi kolom data), baris siap di bawahnya --}}
            @if ($check['invalid'] || $check['valid'])
                <div class="table-wrap">
                    <table class="table imp-table">
                        <thead><tr><th class="num">Baris</th><th>Nama</th><th>NIM</th><th>Email</th><th>Prodi</th><th class="num">Angkatan</th><th class="num">IPK</th></tr></thead>
                        <tbody>
                            @foreach ($check['invalid'] as $row)
                                <tr class="is-invalid">
                                    <td class="num">{{ $row['line'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td colspan="5" class="cell-wrap imp-error"><x-ikon name="alert" :size="15" /> {{ implode(' ', $row['errors']) }}</td>
                                </tr>
                            @endforeach
                            @foreach (array_slice($check['valid'], 0, 50) as $row)
                                <tr>
                                    <td class="num">{{ $row['line'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['nim'] }}</td>
                                    <td>{{ $row['email'] }}</td>
                                    <td>{{ $row['program'] }}</td>
                                    <td class="num">{{ $row['entry_year'] ?? '–' }}</td>
                                    <td class="num">{{ $row['gpa'] !== null ? \App\Support\Num::short($row['gpa']) : '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (count($check['valid']) > 50)<p class="card-foot">Menampilkan 50 dari {{ count($check['valid']) }} baris siap.</p>@endif
            @endif

            <div class="card-body pt">
                <div class="row-actions">
                    <form method="POST" action="{{ route('mahasiswa.impor.batal') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn">Batal</button>
                    </form>
                    @if ($check['valid'])
                        <form method="POST" action="{{ route('mahasiswa.impor.simpan') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary"><x-ikon name="check" :size="16" /> Simpan {{ count($check['valid']) }} mahasiswa</button>
                        </form>
                    @endif
                </div>
                @if ($check['invalid'] && $check['valid'])
                    <p class="hint" style="max-width: none; margin-top: 8px; text-align: right">Baris bermasalah tidak ikut disimpan.</p>
                @endif
            </div>
        </section>
    @else
        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Unggah berkas</h2></div>
                    <form method="POST" action="{{ route('mahasiswa.impor.periksa') }}" enctype="multipart/form-data" class="card-body stack">
                        @csrf
                        <label class="dropzone">
                            <span class="dropzone-icon"><x-ikon name="upload" :size="20" /></span>
                            <span class="dropzone-text">
                                <strong data-dropzone-title>Pilih berkas Excel atau CSV</strong>
                                <span data-dropzone-files data-default=".xlsx atau .csv, maks. 2 MB">.xlsx atau .csv, maks. 2 MB</span>
                                <small>Maks. {{ \App\Actions\Students\ImportStudents::MAX_ROWS }} baris</small>
                            </span>
                            <span class="btn btn-sm dropzone-btn">Telusuri</span>
                            <input name="berkas" type="file" class="dropzone-input" accept=".xlsx,.csv" required
                                   onchange="(function (input) {
                                       var box = input.closest('.dropzone'), f = input.files[0];
                                       box.querySelector('[data-dropzone-title]').textContent = f ? f.name : 'Pilih berkas Excel atau CSV';
                                       box.classList.toggle('has-files', !!f);
                                   })(this)">
                        </label>
                        @error('berkas')<p class="field-error">{{ $message }}</p>@enderror
                        <div class="row-actions">
                            <button type="submit" class="btn btn-primary">Periksa berkas</button>
                        </div>
                    </form>
                </section>
            </div>
            <div class="col">
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Kolom berkas</h2></div>
                    <div class="card-body">
                        <dl class="kv">
                            <div><dt>nama</dt><dd>Wajib</dd></div>
                            <div><dt>email</dt><dd>Wajib, belum terdaftar</dd></div>
                            <div><dt>nim</dt><dd>Wajib, belum terdaftar</dd></div>
                            <div><dt>kode_prodi</dt><dd>{{ $programs->count() === 1 ? 'Boleh kosong ('.$programs->first()->code.')' : 'Wajib' }}</dd></div>
                            <div><dt>angkatan</dt><dd>Opsional</dd></div>
                            <div><dt>ipk</dt><dd>Opsional, 0–4</dd></div>
                        </dl>
                    </div>
                    <p class="card-foot">Kode prodi: {{ $programs->pluck('code')->implode(', ') }}</p>
                </section>
            </div>
        </div>
    @endif
@endsection
