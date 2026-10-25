@extends('layouts.app')

@section('title', 'Hasil KP '.$internship->student->name)

@section('content')
    @php($a = $internship->assessment)
    <div class="page-head">
        <div class="min0">
            <a href="{{ route('hasil.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke Hasil KP</a>
            <h1 class="page-title">{{ $internship->student->name }}</h1>
            <p class="page-sub">{{ $internship->programTypeLabel() }} · {{ $internship->placeName() }} · {{ $internship->periodLabel() }}</p>
        </div>
        @if ($internship->result)<x-chip :tone="$internship->resultTone()">{{ $internship->resultLabel() }}</x-chip>@endif
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Syarat</h2></div>
                <div class="card-body">
                    <ul class="checks">
                        <li @class(['ok' => $a?->isValidated()])>
                            <x-ikon :name="$a?->isValidated() ? 'check' : 'clock'" :size="18" />
                            Penilaian akhir divalidasi
                            @if ($a?->isValidated())<small>Nilai {{ \App\Support\Num::short($a->final_score) }} ({{ $a->letter_grade }})</small>@endif
                        </li>
                        <li @class(['ok' => $guidance >= $min])>
                            <x-ikon :name="$guidance >= $min ? 'check' : 'clock'" :size="18" />
                            Bimbingan terkonfirmasi {{ $guidance }} dari minimal {{ $min }}
                        </li>
                    </ul>
                </div>
            </section>

            @if ($internship->monthlyReviews->whereNotNull('reviewed_at')->isNotEmpty())
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Ringkasan nilai bulanan</h2></div>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Bulan</th>@foreach (\App\Models\MonthlyReview::CATEGORIES as [$col, $label])<th>{{ $label }}</th>@endforeach</tr></thead>
                            <tbody>
                                @foreach ($internship->monthlyReviews->sortBy('month_number') as $r)
                                    <tr>
                                        <td>{{ $r->label() }}</td>
                                        @foreach (\App\Models\MonthlyReview::CATEGORIES as [$col, $label])<td>{{ $r->{$col} ?? '–' }}</td>@endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Keputusan</h2></div>
                <div class="card-body">
                    @if ($internship->result)
                        <dl class="kv">
                            <div><dt>Hasil</dt><dd>{{ $internship->resultLabel() }}</dd></div>
                            @if ($internship->isMbkm())
                                <div><dt>Nilai mitra ({{ $weights[0] }}%)</dt><dd>{{ \App\Support\Num::short($a?->final_score) }}</dd></div>
                                <div><dt>Nilai dosen ({{ $weights[1] }}%)</dt><dd>{{ \App\Support\Num::short($internship->lecturer_score) }}</dd></div>
                            @endif
                            @if ($internship->result_note)<div><dt>Catatan</dt><dd>{{ $internship->result_note }}</dd></div>@endif
                            <div><dt>Diputuskan</dt><dd>{{ $internship->result_at?->locale('id')->translatedFormat('j F Y') }}</dd></div>
                        </dl>
                    @elseif ($blockers)
                        <div class="alert alert-info" role="status">{{ implode(' ', $blockers) }}</div>
                    @elseif ($internship->status !== 'berjalan')
                        <p class="muted">KP ini sudah ditutup.</p>
                    @else
                        <form method="POST" action="{{ route('hasil.update', $internship) }}" class="stack">
                            @csrf
                            @method('PATCH')
                            @if ($internship->isMbkm())
                                <p>Nilai mitra (penilaian akhir Company Supervisor): <strong>{{ \App\Support\Num::short($a->final_score) }}</strong>.</p>
                                <div class="field">
                                    <label class="label" for="lecturer_score">Nilai dosen pembimbing (0–100)</label>
                                    <input id="lecturer_score" type="number" name="lecturer_score" class="input" min="0" max="100" step="0.01" value="{{ old('lecturer_score') }}" required
                                           data-partner="{{ (float) $a->final_score }}" data-pw="{{ $weights[0] }}" data-lw="{{ $weights[1] }}">
                                </div>
                                <p class="hint" id="mbkm-preview">Nilai akhir = {{ $weights[0] }}% mitra + {{ $weights[1] }}% dosen.</p>
                            @else
                                <fieldset class="stack">
                                    <legend class="label">Hasil KP</legend>
                                    @foreach (\App\Models\Internship::RESULTS as $k => [$label])
                                        <label class="check-row"><input type="radio" name="result" value="{{ $k }}" @checked(old('result') === $k) required> <span>{{ $label }}</span></label>
                                    @endforeach
                                </fieldset>
                            @endif
                            <div class="field">
                                <label class="label" for="result_note">Catatan {{ $internship->isMbkm() ? '(opsional)' : '(wajib jika tidak diterima)' }}</label>
                                <textarea id="result_note" name="result_note" class="textarea" rows="3" maxlength="1000">{{ old('result_note') }}</textarea>
                                <p class="hint">Setelah disimpan, KP ditutup dan data tidak bisa diubah lagi.</p>
                            </div>
                            <div class="row-actions"><button type="submit" class="btn btn-primary" onclick="return confirm('Simpan hasil dan tutup KP ini?')"><x-ikon name="check" :size="18" /> Simpan hasil</button></div>
                        </form>
                    @endif
                </div>
            </section>

            @if ($internship->isMbkm() && $internship->creditRecognition)
                @php($cr = $internship->creditRecognition)
                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Rekognisi SKS</h2>
                        <x-chip :tone="$cr->statusTone()">{{ $cr->statusLabel() }}</x-chip>
                    </div>
                    <div class="card-body">
                        <ul class="plain-list">
                            @foreach ($cr->courses as $c)<li>{{ $c['code'] ? $c['code'].' · ' : '' }}{{ $c['name'] }} <span class="muted">({{ $c['credits'] }} SKS)</span></li>@endforeach
                        </ul>
                        <p class="muted">Total {{ $cr->totalCredits() }} SKS</p>
                    </div>
                </section>
            @endif
        </div>
    </div>

    <script>
        (function () {
            var input = document.getElementById('lecturer_score');
            var out = document.getElementById('mbkm-preview');
            if (!input || !out) return;
            input.addEventListener('input', function () {
                var p = parseFloat(input.dataset.partner), l = parseFloat(input.value), pw = +input.dataset.pw, lw = +input.dataset.lw;
                if (isNaN(l)) return;
                var f = Math.round((p * pw + l * lw) / (pw + lw) * 100) / 100;
                out.textContent = 'Perkiraan nilai akhir: ' + f.toString().replace('.', ',') + ' (' + pw + '% mitra + ' + lw + '% dosen).';
            });
        })();
    </script>
@endsection
