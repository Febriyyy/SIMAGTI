@extends('layouts.app')

@section('title', 'Bobot Penilaian')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Bobot Penilaian</h1>
            <p class="page-sub">Bobot tiap kategori dalam persen, total 100%.</p>
        </div>
    </div>

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
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Skala nilai</h2>
                        <p class="card-sub">Standar kampus, diatur admin</p>
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
@endsection
