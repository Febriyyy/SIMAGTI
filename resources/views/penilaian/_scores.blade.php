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
            @foreach ($rubric->aspects->where('kind', '!=', 'text') as $aspect)
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

@php($questions = $rubric->aspects->where('kind', 'text'))
@if ($questions->isNotEmpty())
    <div class="card-body pt">
        <h3 class="form-section-title">Jawaban kuesioner</h3>
        <dl class="answers">
            @foreach ($questions as $aspect)
                <div>
                    <dt>{{ $loop->iteration }}. {{ $aspect->name }}</dt>
                    <dd class="prewrap">{{ $scores->get($aspect->id)?->note ?? '–' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif
