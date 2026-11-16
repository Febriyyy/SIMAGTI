@extends('layouts.app')

@section('title', 'Rubrik Penilaian')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Rubrik Penilaian</h1>
            <p class="page-sub">Pertanyaan tiap kategori dan bobotnya. Company Supervisor tinggal memilih jawaban.</p>
        </div>
    </div>

    @if ($used > 0)
        <div class="alert alert-info" role="status">Rubrik ini sudah dipakai {{ $used }} penilaian. Perubahan pertanyaan dibuat sebagai versi baru, penilaian lama tidak berubah.</div>
    @endif

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Bobot kategori</h2>
                        <p class="card-sub">Dipakai untuk penilaian yang dikirim setelah disimpan</p>
                    </div>
                    <x-chip :tone="abs(array_sum($weights) - 100) < 0.001 ? 'green' : 'red'" data-weight-total>{{ \App\Support\Num::short(array_sum($weights)) }}%</x-chip>
                </div>
                <form method="POST" action="{{ route('kaprodi.bobot.update') }}" data-weight-form>
                    @csrf
                    @method('PUT')
                    <ul class="weight-list">
                        @foreach (\App\Support\AssessmentStandard::CATEGORIES as $key => [$label, $hint])
                            @php($count = $rubric->aspects->where('category', $key)->count())
                            <li class="weight-row">
                                <label for="w-{{ $key }}" class="weight-label">
                                    <strong>{{ $label }}</strong>
                                    <small>{{ $hint }} · {{ $count }} pertanyaan</small>
                                </label>
                                <div class="weight-input">
                                    <input id="w-{{ $key }}" type="number" name="weights[{{ $key }}]" class="input" min="0" max="100" step="0.5" required
                                           value="{{ old('weights.'.$key, 0 + $weights[$key]) }}" inputmode="decimal">
                                    <span>%</span>
                                </div>
                                <div class="weight-bar" aria-hidden="true"><span style="width: {{ min(100, $weights[$key]) }}%"></span></div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="form-actions">
                        <p class="hint">Nilai kategori = rata-rata nilai pertanyaannya. Nilai akhir = jumlah nilai kategori × bobot.</p>
                        <div class="row-actions"><button type="submit" class="btn btn-primary">Simpan</button></div>
                    </div>
                </form>
            </section>

            <section class="card" id="pertanyaan">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Pertanyaan</h2>
                        <p class="card-sub">{{ $rubric->aspects->count() }} pertanyaan dalam 3 kategori</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-open="dlg-question"><x-ikon name="plus" :size="16" /> Pertanyaan</button>
                </div>
                @error('name')<p class="field-error rate-error">{{ $message }}</p>@enderror
                @foreach (\App\Support\AssessmentStandard::CATEGORIES as $key => [$label, $hint])
                    @php($aspects = $rubric->aspects->where('category', $key))
                    <div class="rate-group">
                        <div class="rate-head">
                            <div>
                                <h3 class="rate-title">{{ $label }}</h3>
                                <p class="rate-sub">{{ $hint }}</p>
                            </div>
                            <x-chip tone="gray">{{ $aspects->count() }}</x-chip>
                        </div>
                        <ol class="rate-list">
                            @foreach ($aspects as $aspect)
                                <li class="rate-row">
                                    <span class="rate-q">{{ $aspect->name }}@if ($aspect->description)<small>{{ $aspect->description }}</small>@endif</span>
                                    <span class="row-actions">
                                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-open="dlg-q-{{ $aspect->id }}" aria-label="Ubah {{ $aspect->name }}"><x-ikon name="edit" :size="16" /></button>
                                        <form method="POST" action="{{ route('kaprodi.pertanyaan.destroy', $aspect) }}" onsubmit="return confirm('Hapus pertanyaan {{ $aspect->name }}?')">
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
                        <h2 class="card-title">Pilihan jawaban</h2>
                        <p class="card-sub">Nilai tiap jawaban, diatur admin</p>
                    </div>
                </div>
                <ul class="item-list">
                    @foreach ($scale as $row)
                        <li class="item-row">
                            <span class="grow cell-title">{{ $row['label'] }}</span>
                            <span class="num">{{ \App\Support\Num::short($row['value']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Contoh perhitungan</h2>
                </div>
                <div class="card-body">
                    @php($example = ['hard' => 85, 'soft' => 90, 'attitude' => 95])
                    <dl class="kv">
                        @foreach ($example as $key => $avg)
                            <div><dt>{{ \App\Support\AssessmentStandard::categoryLabel($key) }}</dt><dd>{{ $avg }} × {{ \App\Support\Num::short($weights[$key]) }}%<small>= {{ \App\Support\Num::short($avg * $weights[$key] / 100) }}</small></dd></div>
                        @endforeach
                        <div><dt>Nilai akhir</dt><dd>{{ \App\Support\Num::short(collect($example)->map(fn ($v, $k) => $v * $weights[$k] / 100)->sum()) }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
    <dialog id="dlg-question" class="dialog" @if ($errors->has('name') && old('_new')) data-autoopen @endif>
        <form method="POST" action="{{ route('kaprodi.pertanyaan.store') }}" class="dialog-body">
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
            <form method="POST" action="{{ route('kaprodi.pertanyaan.update', $aspect) }}" class="dialog-body">
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
