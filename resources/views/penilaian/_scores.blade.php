{{-- Rincian nilai per kategori (hanya baca). Variabel: $rubric, $assessment --}}
@php($scores = $assessment ? $assessment->scores->keyBy('rubric_aspect_id') : collect())
@php($sum = \App\Support\AssessmentStandard::breakdown($rubric, $assessment?->scores ?? collect(), \App\Support\AssessmentStandard::weightsFor($assessment)))
@foreach (\App\Support\AssessmentStandard::CATEGORIES as $key => [$label, $hint])
    @php($cat = $sum['categories'][$key])
    @php($aspects = $rubric->aspects->where('category', $key)->where('kind', '!=', 'text'))
    <div class="rate-group">
        <div class="rate-head">
            <div>
                <h3 class="rate-title">{{ $label }}</h3>
                <p class="rate-sub">Bobot {{ \App\Support\Num::short($cat['weight']) }}% · {{ $cat['answered'] }}/{{ $cat['total'] }} dinilai</p>
            </div>
            <div class="rate-score">
                <strong>{{ $cat['average'] !== null ? \App\Support\Num::short($cat['average']) : '–' }}</strong>
                <small>kontribusi {{ \App\Support\Num::short($cat['contribution']) }}</small>
            </div>
        </div>
        <ul class="rate-list">
            @foreach ($aspects as $aspect)
                @php($value = $scores->get($aspect->id)?->score)
                <li class="rate-row">
                    <span class="rate-q">{{ $aspect->name }}</span>
                    <span class="rate-answer">
                        @if ($value !== null)
                            {{ \App\Support\AssessmentStandard::scaleLabel((float) $value) ?? 'Nilai' }} <span class="muted">· {{ \App\Support\Num::short((float) $value) }}</span>
                        @else
                            <span class="muted">–</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endforeach
<div class="rate-total">
    <span>Nilai akhir</span>
    <strong>{{ \App\Support\Num::short($assessment?->final_score ?? $sum['final']) }}</strong>
</div>
@if ($assessment?->comment)
    <div class="card-body pt">
        <h3 class="form-section-title">Catatan Company Supervisor</h3>
        <p class="prewrap">{{ $assessment->comment }}</p>
    </div>
@endif
