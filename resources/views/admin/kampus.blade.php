@extends('layouts.app')

@section('title', 'Kampus dan Prodi')

{{--
    Admin: identitas kampus (dipakai di surat pengantar, sertifikat, berita acara, rekap)
    dan daftar program studi beserta kaprodinya. Kaprodi yang ditunjuk hanya melihat mahasiswa prodinya.
    Variabel: campus (kunci => nilai), programs, kaprodis
--}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Kampus dan Prodi</h1>
            <p class="page-sub">Identitas kampus dan program studi.</p>
        </div>
    </div>

    <div class="layout">
        <div class="col">
            {{-- Program studi --}}
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Program studi</h2>
                        <p class="card-sub">{{ $programs->count() }} prodi</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-open="dlg-prodi"><x-ikon name="plus" :size="16" /> Prodi</button>
                </div>
                <ul class="prd-list">
                    @foreach ($programs as $program)
                        <?php
                            $bag = $errors->getBag('prodi'.$program->id);
                            $current = $kaprodis->first(fn ($k) => (int) $k->lecturerProfile?->study_program_id === (int) $program->id);
                        ?>
                        @if ($loop->first || $programs[$loop->index - 1]->faculty !== $program->faculty)
                            <li class="prd-faculty">{{ $program->faculty ?: 'Fakultas belum diisi' }}</li>
                        @endif
                        <li class="prd-row">
                            <form method="POST" action="{{ route('admin.prodi.update', $program) }}" class="prd-form">
                                @csrf
                                @method('PUT')
                                <div class="prd-meta"><span class="prd-code">{{ $program->code }}</span><span>{{ $program->students_count }} mahasiswa</span></div>
                                <div class="prd-field">
                                    <label class="label" for="prd-name-{{ $program->id }}">Nama prodi</label>
                                    <input id="prd-name-{{ $program->id }}" name="name" class="input" maxlength="100" required value="{{ old('name', $program->name) }}">
                                    @if ($bag->has('name'))<p class="field-error">{{ $bag->first('name') }}</p>@endif
                                </div>
                                <div class="prd-field">
                                    <label class="label" for="prd-kap-{{ $program->id }}">Kaprodi</label>
                                    <select id="prd-kap-{{ $program->id }}" name="kaprodi_id" class="select">
                                        <option value="">Belum ditunjuk</option>
                                        @foreach ($kaprodis as $k)
                                            <option value="{{ $k->id }}" @selected($current?->id === $k->id)>{{ $k->name }}{{ $k->lecturerProfile?->study_program_id && (int) $k->lecturerProfile->study_program_id !== (int) $program->id ? ' (prodi lain)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm">Simpan</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <p class="card-foot">Kaprodi tanpa prodi melihat semua prodi.</p>
            </section>
        </div>

        <div class="col">
            {{-- Identitas kampus --}}
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Identitas kampus</h2>
                        <p class="card-sub">Tampil di surat, sertifikat, dan berita acara</p>
                    </div>
                </div>
                <?php $kb = $errors->getBag('kampus'); ?>
                <form method="POST" action="{{ route('admin.kampus.update') }}" class="card-body stack">
                    @csrf
                    @method('PUT')
                    @foreach (\App\Http\Controllers\AdminCampusController::FIELDS as $key => [$label, $example])
                        <div>
                            <label class="label" for="kampus-{{ $key }}">{{ $label }}</label>
                            <input id="kampus-{{ $key }}" name="{{ $key }}" class="input" maxlength="200" required placeholder="{{ $example }}" value="{{ old($key, $campus[$key]) }}">
                            @if ($kb->has($key))<p class="field-error">{{ $kb->first($key) }}</p>@endif
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary">Simpan identitas</button>
                </form>
            </section>
        </div>
    </div>

    {{-- Dialog tambah prodi --}}
    <?php $pb = $errors->getBag('prodi'); ?>
    <dialog id="dlg-prodi" class="dialog" @if ($pb->any()) data-autoopen @endif>
        <form method="POST" action="{{ route('admin.prodi.store') }}" class="dialog-body">
            @csrf
            <div class="dialog-head">
                <div>
                    <h2 class="card-title">Tambah program studi</h2>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
            </div>
            <div class="form-grid">
                <div>
                    <label class="label" for="new-code">Kode</label>
                    <input id="new-code" name="code" class="input" maxlength="10" required placeholder="SI" value="{{ old('code') }}">
                    @if ($pb->has('code'))<p class="field-error">{{ $pb->first('code') }}</p>@endif
                </div>
                <div>
                    <label class="label" for="new-name">Nama prodi</label>
                    <input id="new-name" name="name" class="input" maxlength="100" required placeholder="Sistem Informasi" value="{{ old('name') }}">
                    @if ($pb->has('name'))<p class="field-error">{{ $pb->first('name') }}</p>@endif
                </div>
            </div>
            <div class="field">
                <label class="label" for="new-faculty">Fakultas</label>
                <input id="new-faculty" name="faculty" class="input" maxlength="120" required list="faculty-options"
                       value="{{ old('faculty', $faculties->first() ?? \App\Support\Campus::faculty()) }}" placeholder="Fakultas Ilmu Komputer">
                <datalist id="faculty-options">@foreach ($faculties as $f)<option value="{{ $f }}">@endforeach</datalist>
                @if ($pb->has('faculty'))<p class="field-error">{{ $pb->first('faculty') }}</p>@endif
            </div>
            <div class="dialog-foot row-actions">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Tambah</button>
            </div>
        </form>
    </dialog>
@endsection
