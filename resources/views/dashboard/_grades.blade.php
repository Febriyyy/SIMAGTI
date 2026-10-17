{{-- Grafik sebaran huruf mutu. Variabel: $grades (huruf => jumlah) --}}
@php($max = max(1, max($grades ?: [0])))
@if (array_sum($grades) === 0)
    <p class="muted">Belum ada penilaian yang divalidasi.</p>
@else
    <div class="grade-bars" role="img" aria-label="Sebaran huruf mutu: {{ collect($grades)->map(fn ($n, $g) => "$g $n")->implode(', ') }}">
        @foreach ($grades as $grade => $count)
            <div @class(['grade-bar', 'is-empty' => $count === 0])>
                <span class="grade-bar-count">{{ $count }}</span>
                <span class="grade-bar-fill" style="height: {{ $count === 0 ? 3 : max(8, (int) round($count / $max * 80)) }}%"></span>
                <span class="grade-bar-label">{{ $grade }}</span>
            </div>
        @endforeach
    </div>
@endif
