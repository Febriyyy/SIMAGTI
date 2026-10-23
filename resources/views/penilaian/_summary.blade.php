{{-- Ringkasan nilai akhir satu penilaian. Variabel: $assessment (boleh null) --}}
@php
    $version = $assessment?->grade_scale_version ?? \App\Support\Grading::currentVersion();
    $scale = \App\Models\GradeScale::where('version', $version)->orderByDesc('min_score')->get();
    $letter = $assessment?->letter();
@endphp
<section class="card">
    <div class="card-head">
        <h2 class="card-title">Nilai akhir</h2>
        @if ($assessment)
            <x-chip :tone="$assessment->statusTone()">{{ $assessment->statusLabel() }}</x-chip>
        @else
            <x-chip tone="gray">Belum dinilai</x-chip>
        @endif
    </div>
    <div class="card-body">
        @if ($letter)
            <div class="score-big">
                <span class="score-letter">{{ $letter }}</span>
                <span class="score-number">{{ \App\Support\Num::short($assessment->final_score) }}<small>/100</small></span>
            </div>
        @else
            <p class="cell-title" style="margin-bottom: 4px">Belum ada nilai</p>
        @endif
        <p class="hint" style="margin-bottom: 14px">
            @if ($assessment?->isValidated())
                Dikunci {{ $assessment->validated_at->locale('id')->translatedFormat('j M Y') }} oleh {{ $assessment->validator?->name ?? 'dosen pembimbing' }} · skala versi {{ $assessment->grade_scale_version }}.
            @else
                @if ($assessment && ! $assessment->isValidated() && $letter) Huruf di atas masih perkiraan. @endif Nilai akhir = jumlah (nilai aspek × bobot). Huruf mutu baru dikunci saat dosen pembimbing memvalidasi.
            @endif
        </p>
        <dl class="kv kv-tight">
            @foreach ($scale as $row)
                <div @class(['is-current' => $row->grade === $letter])>
                    <dt>{{ $row->grade }}</dt>
                    <dd>≥ {{ \App\Support\Num::short($row->min_score) }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
