@extends('layouts.app')

@section('title', 'Standar Penilaian')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Standar Penilaian</h1>
            <p class="page-sub">Pertanyaan, skala nilai, dan huruf mutu untuk penilaian akhir.</p>
        </div>
    </div>

    @if ($used > 0)
        <div class="alert alert-info" role="status">Standar ini sudah dipakai {{ $used }} penilaian. Perubahan pertanyaan dibuat sebagai versi baru, penilaian lama tidak berubah.</div>
    @endif

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Pertanyaan</h2>
                        <p class="card-sub">{{ $rubric->aspects->count() }} pertanyaan dalam 3 kategori</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-open="dlg-question"><x-ikon name="plus" :size="16" /> Pertanyaan</button>
                </div>
                @foreach (\App\Support\AssessmentStandard::CATEGORIES as $key => [$label, $hint])
                    @php($aspects = $rubric->aspects->where('category', $key))
                    <div class="rate-group">
                        <div class="rate-head">
                            <div>
                                <h3 class="rate-title">{{ $label }}</h3>
                                <p class="rate-sub">{{ $hint }} · bobot {{ \App\Support\Num::short($weights[$key]) }}% (diatur kaprodi)</p>
                            </div>
                            <x-chip tone="gray">{{ $aspects->count() }}</x-chip>
                        </div>
                        <ol class="rate-list">
                            @foreach ($aspects as $aspect)
                                <li class="rate-row">
                                    <span class="rate-q">{{ $aspect->name }}@if ($aspect->description)<small>{{ $aspect->description }}</small>@endif</span>
                                    <span class="row-actions">
                                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-open="dlg-q-{{ $aspect->id }}" aria-label="Ubah {{ $aspect->name }}"><x-ikon name="edit" :size="16" /></button>
                                        <form method="POST" action="{{ route('admin.penilaian.pertanyaan.destroy', $aspect) }}" onsubmit="return confirm('Hapus pertanyaan {{ $aspect->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" aria-label="Hapus {{ $aspect->name }}"><x-ikon name="delete" :size="16" /></button>
                                        </form>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Skala nilai</h2>
                        <p class="card-sub">Pilihan yang diklik Company Supervisor</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.penilaian.skala') }}" class="card-body stack">
                    @csrf
                    @method('PUT')
                    @php($rows = old('scale', $scale))
                    @for ($i = 0; $i < min(7, count($rows) + 1); $i++)
                        @php($row = $rows[$i] ?? null)
                        <div class="scale-row">
                            <input type="text" name="scale[{{ $i }}][label]" class="input input-sm" maxlength="30" value="{{ $row['label'] ?? '' }}" placeholder="Label" aria-label="Label pilihan {{ $i + 1 }}">
                            <input type="number" name="scale[{{ $i }}][value]" class="input input-sm" min="0" max="100" step="0.5" value="{{ isset($row['value']) ? 0 + $row['value'] : '' }}" placeholder="Nilai" aria-label="Nilai pilihan {{ $i + 1 }}">
                        </div>
                    @endfor
                    <p class="hint">2–7 pilihan. Isi baris kosong untuk menambah, kosongkan baris untuk menghapus. Nilai lama yang sudah tersimpan tidak berubah.</p>
                    <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
                </form>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Huruf mutu</h2>
                        <p class="card-sub">Batas bawah nilai akhir</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.penilaian.huruf') }}" class="card-body stack">
                    @csrf
                    @method('PUT')
                    <div class="grade-grid">
                        @foreach ($grades as $grade => $min)
                            <label class="grade-cell">
                                <span>{{ $grade }}</span>
                                <input type="number" name="grades[{{ $grade }}]" class="input input-sm" min="0" max="100" step="0.5" value="{{ old('grades.'.$grade, 0 + $min) }}" aria-label="Batas bawah {{ $grade }}" required>
                            </label>
                        @endforeach
                    </div>
                    <p class="hint">Nilai yang sudah divalidasi tetap memakai skala saat divalidasi.</p>
                    <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
                </form>
            </section>
        </div>
    </div>

    <dialog id="dlg-question" class="dialog" @if ($errors->has('name') && old('_new')) data-autoopen @endif>
        <form method="POST" action="{{ route('admin.penilaian.pertanyaan.store') }}" class="dialog-body">
            @csrf
            <input type="hidden" name="_new" value="1">
            <div class="dialog-head">
                <h2 class="card-title">Pertanyaan baru</h2>
                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
            </div>
            <div class="stack">
                <div>
                    <label class="label" for="q-category">Kategori</label>
                    <select id="q-category" name="category" class="select" required>
                        @foreach (\App\Support\AssessmentStandard::CATEGORIES as $key => [$label])
                            <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="q-name">Pertanyaan</label>
                    <input id="q-name" type="text" name="name" class="input" maxlength="150" required value="{{ old('_new') ? old('name') : '' }}" placeholder="Misalnya Ketelitian kerja">
                </div>
                <div>
                    <label class="label" for="q-desc">Keterangan (opsional)</label>
                    <input id="q-desc" type="text" name="description" class="input" maxlength="300" value="{{ old('_new') ? old('description') : '' }}">
                </div>
            </div>
            <div class="dialog-foot row-actions">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </dialog>

    @foreach ($rubric->aspects as $aspect)
        <dialog id="dlg-q-{{ $aspect->id }}" class="dialog">
            <form method="POST" action="{{ route('admin.penilaian.pertanyaan.update', $aspect) }}" class="dialog-body">
                @csrf
                @method('PATCH')
                <div class="dialog-head">
                    <h2 class="card-title">Ubah pertanyaan</h2>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div class="stack">
                    <div>
                        <label class="label" for="q-name-{{ $aspect->id }}">Pertanyaan · {{ $aspect->categoryLabel() }}</label>
                        <input id="q-name-{{ $aspect->id }}" type="text" name="name" class="input" maxlength="150" required value="{{ $aspect->name }}">
                    </div>
                    <div>
                        <label class="label" for="q-desc-{{ $aspect->id }}">Keterangan (opsional)</label>
                        <input id="q-desc-{{ $aspect->id }}" type="text" name="description" class="input" maxlength="300" value="{{ $aspect->description }}">
                    </div>
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </dialog>
    @endforeach
@endsection
