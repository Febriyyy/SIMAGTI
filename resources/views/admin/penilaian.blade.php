@extends('layouts.app')

@section('title', 'Standar Nilai')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Standar Nilai</h1>
            <p class="page-sub">Nilai tiap pilihan jawaban dan huruf mutu. Pertanyaan diatur kaprodi.</p>
        </div>
    </div>

    <div class="split">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Pilihan jawaban</h2>
                        <p class="card-sub">Diklik Company Supervisor, tiap pilihan punya nilai</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.penilaian.skala') }}" class="card-body stack">
                    @csrf
                    @method('PUT')
                    @php($rows = old('scale', $scale))
                    @for ($i = 0; $i < min(7, count($rows) + 1); $i++)
                        @php($row = $rows[$i] ?? null)
                        <div class="scale-row">
                            <input type="text" name="scale[{{ $i }}][label]" class="input input-sm" maxlength="30" value="{{ $row['label'] ?? '' }}" placeholder="Jawaban" aria-label="Label pilihan {{ $i + 1 }}">
                            <input type="number" name="scale[{{ $i }}][value]" class="input input-sm" min="0" max="100" step="0.5" value="{{ isset($row['value']) ? 0 + $row['value'] : '' }}" placeholder="Nilai" aria-label="Nilai pilihan {{ $i + 1 }}">
                        </div>
                    @endfor
                    <p class="hint">2–7 pilihan. Isi baris kosong untuk menambah, kosongkan baris untuk menghapus. Nilai lama yang sudah tersimpan tidak berubah.</p>
                    <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
                </form>
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Huruf mutu</h2>
                        <p class="card-sub">Sesuai standar kampus</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.penilaian.huruf') }}" class="card-body stack">
                    @csrf
                    @method('PUT')
                    <?php
                        $gradeRows = old('grades', collect($grades)->map(fn ($min, $letter) => ['letter' => $letter, 'min' => 0 + $min])->values()->all());
                        // Rentang tiap huruf: dari batas bawahnya sampai sebelum batas huruf di atasnya
                        $ranges = [];
                        $upper = null;
                        foreach (collect($grades)->sortDesc() as $letter => $min) {
                            $ranges[$letter] = \App\Support\Num::short($min).'–'.($upper === null ? '100' : \App\Support\Num::short($upper - 0.1, 1));
                            $upper = $min;
                        }
                    ?>
                    <div class="grade-table" role="group" aria-label="Huruf mutu dan batas bawah">
                        <div class="grade-row grade-row-head" aria-hidden="true"><span>Huruf</span><span>Batas bawah</span><span>Rentang</span></div>
                        @for ($i = 0; $i < min(12, count($gradeRows) + 2); $i++)
                            @php($row = $gradeRows[$i] ?? null)
                            <div class="grade-row">
                                <input type="text" name="grades[{{ $i }}][letter]" class="input input-sm grade-letter" maxlength="3" value="{{ $row['letter'] ?? '' }}" placeholder="–" aria-label="Huruf mutu baris {{ $i + 1 }}">
                                <input type="number" name="grades[{{ $i }}][min]" class="input input-sm" min="0" max="100" step="0.1" value="{{ $row['min'] ?? '' }}" placeholder="Nilai" aria-label="Batas bawah baris {{ $i + 1 }}">
                                <span class="grade-range">{{ isset($row['letter']) ? ($ranges[$row['letter']] ?? '') : '' }}</span>
                            </div>
                        @endfor
                    </div>
                    @error('grades')<p class="field-error">{{ $message }}</p>@enderror
                    @foreach ($errors->get('grades.*') as $messages)<p class="field-error">{{ $messages[0] }}</p>@break @endforeach
                    <p class="hint">Isi baris kosong untuk menambah, kosongkan baris untuk menghapus. Nilai di bawah batas terendah mendapat huruf E. Nilai yang sudah divalidasi tetap memakai skala lamanya.</p>
                    <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm">Simpan</button></div>
                </form>
            </section>
        </div>
    </div>

@endsection
