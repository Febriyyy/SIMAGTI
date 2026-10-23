{{--
    Diagram lingkaran (donut) untuk bagian-dari-keseluruhan, maksimal 4–5 bagian.
    Tanpa library: lingkaran SVG digambar di server, tooltip dari public/js/charts.js.

    segments : [['label' => 'Tervalidasi', 'value' => 3, 'color' => 'green'], ...]
               warna: green | blue | amber | gray (urutan tetap, warna mengikuti makna, bukan urutan)
    caption  : teks kecil di bawah angka total di tengah lingkaran
    unit     : satuan di tooltip, misalnya "mahasiswa"

    Legenda selalu tampil dengan angka dan persen, jadi arti tidak bergantung pada warna saja.
--}}
@props(['segments' => [], 'caption' => 'total', 'unit' => '', 'empty' => 'Belum ada data'])
@php
    $segments = collect($segments)->map(fn ($s) => $s + ['color' => 'gray'])->values();
    $total = (int) $segments->sum('value');
    $r = 50;
    $circumference = 2 * M_PI * $r;
    $filled = $segments->where('value', '>', 0)->count();
    $gap = $filled > 1 ? 2.2 : 0; // celah 2px antar bagian

    $arcs = [];
    $offset = 0;
    foreach ($segments as $i => $s) {
        if ($s['value'] <= 0) {
            continue;
        }
        $length = $s['value'] / $total * $circumference;
        $arcs[] = $s + [
            'key' => $i,
            'dash' => max($length - $gap, 0.5),
            'offset' => -$offset,
        ];
        $offset += $length;
    }
    $pct = fn ($v) => $total > 0 ? (int) round($v / $total * 100) : 0;
    $aria = $segments->map(fn ($s) => $s['label'].' '.$s['value'])->implode(', ');
@endphp
<div {{ $attributes->class(['donut']) }} data-chart>
    <div class="donut-figure">
        <svg viewBox="0 0 120 120" class="donut-svg" role="img" aria-label="{{ $aria }}">
            <circle class="donut-track" cx="60" cy="60" r="{{ $r }}" />
            <g transform="rotate(-90 60 60)">
                @foreach ($arcs as $arc)
                    <circle class="donut-seg tone-{{ $arc['color'] }}" cx="60" cy="60" r="{{ $r }}"
                            stroke-dasharray="{{ round($arc['dash'], 2) }} {{ round($circumference, 2) }}"
                            stroke-dashoffset="{{ round($arc['offset'], 2) }}"
                            data-key="{{ $arc['key'] }}"
                            data-tip="{{ $arc['label'] }}: {{ $arc['value'] }} {{ $unit }} ({{ $pct($arc['value']) }}%)" />
                @endforeach
            </g>
        </svg>
        <div class="donut-center">
            @if ($total > 0)
                <strong>{{ $total }}</strong>
                <span>{{ $caption }}</span>
            @else
                <span>{{ $empty }}</span>
            @endif
        </div>
    </div>

    <ul class="legend legend-list">
        @foreach ($segments as $i => $s)
            <li data-key="{{ $i }}" @class(['is-zero' => $s['value'] <= 0])>
                <span class="legend-swatch tone-{{ $s['color'] }}"></span>
                <span class="legend-label">{{ $s['label'] }}</span>
                <span class="legend-value">{{ $s['value'] }}</span>
                <span class="legend-pct">{{ $pct($s['value']) }}%</span>
            </li>
        @endforeach
    </ul>
</div>
