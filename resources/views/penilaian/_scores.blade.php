{{-- Tabel nilai per aspek (hanya baca). Variabel: $rubric, $assessment --}}
@php($scores = $assessment ? $assessment->scores->keyBy('rubric_aspect_id') : collect())
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Aspek</th>
                <th class="col-num">Bobot</th>
                <th class="col-num">Nilai</th>
                <th class="col-num">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rubric->aspects as $aspect)
                @php($s = $scores->get($aspect->id))
                <tr>
                    <td class="cell-wrap">
                        <div class="cell-title">{{ $aspect->name }}</div>
                        @if ($s?->note)
                            <div class="cell-sub">“{{ $s->note }}”</div>
                        @elseif ($aspect->description)
                            <div class="cell-sub">{{ $aspect->description }}</div>
                        @endif
                    </td>
                    <td class="col-num">{{ \App\Support\Num::short($aspect->weight) }}%</td>
                    <td class="col-num cell-title">{{ \App\Support\Num::short($s?->score) }}</td>
                    <td class="col-num muted">{{ $s ? \App\Support\Num::short((float) $s->score * (float) $aspect->weight / 100) : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
        @if ($assessment?->final_score !== null)
            <tfoot>
                <tr>
                    <td class="cell-title">Nilai akhir</td>
                    <td class="col-num">100%</td>
                    <td></td>
                    <td class="col-num cell-title">{{ \App\Support\Num::short($assessment->final_score) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
