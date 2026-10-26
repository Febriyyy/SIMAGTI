{{-- Ringkasan nilai akhir satu penilaian. Variabel: $assessment (boleh null) --}}
@php
    $version = $assessment?->grade_scale_version ?? \App\Support\Grading::currentVersion();
    $scale = collect(\App\Support\Grading::scale($version))->map(fn ($min, $grade) => (object) ['grade' => $grade, 'min_score' => $min])->values();
    $complete = $assessment && ($assessment->status !== 'draft' || $assessment->breakdown()['complete']);
    // Draft yang belum semua pertanyaannya dinilai belum punya perkiraan nilai yang berarti
    $letter = $complete ? $assessment->letter() : null;
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
            <p class="cell-title" style="margin-bottom: var(--space-1)">{{ $assessment ? 'Belum semua pertanyaan dinilai' : 'Belum ada nilai' }}</p>
        @endif
        <p class="hint" style="margin-bottom: var(--space-4)">
            @if ($assessment?->isValidated())
                Dikunci {{ $assessment->validated_at->locale('id')->translatedFormat('j M Y') }} oleh {{ $assessment->validator?->name ?? 'dosen pembimbing' }} · skala versi {{ $assessment->grade_scale_version }}.
            @else
                @if ($assessment && ! $assessment->isValidated() && $letter) Huruf di atas masih perkiraan. @endif Nilai akhir = jumlah (rata-rata tiap kategori × bobot kategori). Huruf mutu dikunci saat dosen pembimbing memvalidasi.
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
