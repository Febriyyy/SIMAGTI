@extends('layouts.app')

@section('title', $rubric->name)

@section('content')
    <div class="page-head">
        <div class="min0">
            <a href="{{ route('kuesioner.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke Kuesioner</a>
            <h1 class="page-title">{{ $rubric->name }}</h1>
            <p class="page-sub">Total bobot aspek bernilai harus 100%.</p>
        </div>
    </div>

    @if ($locked)
        <div class="alert alert-info" role="status">Kuesioner ini sudah dipakai menilai mahasiswa, jadi tidak bisa diubah. Buat salinannya dari halaman Kuesioner.</div>
    @endif

    <form method="POST" action="{{ route('kuesioner.update', $rubric) }}" class="card">
        @csrf
        @method('PUT')
        <fieldset @disabled($locked) class="plain-fieldset">
            <div class="card-body pt stack">
                <div class="field">
                    <label class="label" for="name">Nama kuesioner</label>
                    <input id="name" type="text" name="name" class="input" maxlength="120" value="{{ old('name', $rubric->name) }}" required>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table questionnaire-table">
                    <thead><tr><th>Aspek / pertanyaan</th><th>Keterangan</th><th>Jenis</th><th>Bobot %</th><th></th></tr></thead>
                    <tbody data-rows>
                        @php($rows = old('aspects', $rubric->aspects->map(fn ($a) => $a->only(['name', 'description', 'kind', 'weight']))->all()))
                        @foreach ($rows as $i => $a)
                            @include('kuesioner._row', ['i' => $i, 'a' => $a])
                        @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="3" class="cell-title">Total bobot</td><td data-total>–</td><td></td></tr></tfoot>
                </table>
            </div>

            <div class="card-body form-actions">
                <button type="button" class="btn" data-add-row><x-ikon name="plus" :size="18" /> Tambah baris</button>
                <button type="submit" class="btn btn-primary"><x-ikon name="check" :size="18" /> Simpan kuesioner</button>
            </div>
        </fieldset>
    </form>

    <template id="row-template">@include('kuesioner._row', ['i' => '__i__', 'a' => ['kind' => 'score']])</template>

    <script>
        (function () {
            var body = document.querySelector('[data-rows]');
            var total = document.querySelector('[data-total]');
            var tpl = document.getElementById('row-template');
            var next = body.children.length;
            function sum() {
                var t = 0;
                body.querySelectorAll('tr').forEach(function (tr) {
                    var kind = tr.querySelector('[data-kind]').value;
                    var w = tr.querySelector('[data-weight]');
                    w.disabled = kind !== 'score' || tr.closest('fieldset').disabled;
                    if (kind === 'score' && tr.querySelector('[data-name]').value.trim() !== '') t += parseFloat(w.value || 0);
                });
                total.textContent = (Math.round(t * 100) / 100).toString().replace('.', ',') + '%';
                total.classList.toggle('text-red', Math.abs(t - 100) > 0.001);
            }
            document.querySelector('[data-add-row]').addEventListener('click', function () {
                body.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__i__/g, next++));
                sum();
            });
            body.addEventListener('input', sum);
            body.addEventListener('change', sum);
            body.addEventListener('click', function (e) {
                var b = e.target.closest('[data-remove]');
                if (b) { b.closest('tr').remove(); sum(); }
            });
            sum();
        })();
    </script>
@endsection
