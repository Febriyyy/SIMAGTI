{{--
    Grafik garis untuk perubahan dari waktu ke waktu. Satu sumbu Y saja.
    Tanpa library: SVG digambar di server, tooltip dari public/js/charts.js.

    labels : label sumbu X, misalnya ['1', '2', '3'] atau ['5 Okt', '12 Okt']
    tips   : (opsional) judul tooltip per titik; bawaan = label
    series : [['name' => 'Task selesai', 'values' => [..], 'color' => 'green', 'area' => true],
              ['name' => 'Task diberikan', 'values' => [..], 'color' => 'gray', 'dashed' => true]]
             Seri pertama adalah seri utama: diberi area dan angka di titik terakhir.
    unit   : satuan di tooltip
    width  : lebar dasar gambar; samakan kira-kira dengan lebar kartu supaya ukuran huruf pas (±1:1)
--}}
@props(['labels' => [], 'tips' => null, 'series' => [], 'unit' => '', 'width' => 440, 'height' => 200])
@php
    $labels = array_values($labels);
    $tips = $tips ? array_values($tips) : $labels;
    $series = collect($series)->map(fn ($s) => $s + ['color' => 'green', 'area' => false, 'dashed' => false])->values();
    $n = count($labels);

    $W = (int) $width; $H = (int) $height;
    $padL = 36; $padR = 22; $padT = 18; $padB = 30;
    $plotW = $W - $padL - $padR; $plotH = $H - $padT - $padB;

    // Skala Y "rapi": 4 garis bantu dengan langkah 1, 2, 5, 10, ...
    $max = max(1, (float) $series->flatMap(fn ($s) => $s['values'])->max());
    $raw = $max / 4;
    $mag = 10 ** floor(log10($raw));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $mag)->first(fn ($v) => $v >= $raw);
    $step = max(1, $step);
    $top = $step * 4;

    $x = fn (int $i) => $n > 1 ? $padL + $i * $plotW / ($n - 1) : $padL + $plotW / 2;
    $y = fn (float $v) => $padT + (1 - $v / $top) * $plotH;
    $base = $padT + $plotH;
    $every = max(1, (int) ceil($n / max(3, floor($plotW / 48)))); // label sumbu X tidak berdesakan
    $fmt = fn ($v) => \App\Support\Num::short($v);
@endphp
<div {{ $attributes->class(['line-chart']) }} data-chart>
    @if ($series->count() > 1)
        <ul class="legend legend-inline">
            @foreach ($series as $s)
                <li><span class="legend-line tone-{{ $s['color'] }} {{ $s['dashed'] ? 'is-dashed' : '' }}"></span>{{ $s['name'] }}</li>
            @endforeach
        </ul>
    @endif

    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="lc-svg" role="img"
         aria-label="{{ $series->map(fn ($s) => $s['name'].': '.implode(', ', $s['values']))->implode('; ') }}">
        {{-- Garis bantu dan angka sumbu Y --}}
        @for ($t = 0; $t <= 4; $t++)
            @php $gy = $y($step * $t); @endphp
            <line class="lc-grid {{ $t === 0 ? 'is-base' : '' }}" x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ $gy }}" y2="{{ $gy }}" />
            <text class="lc-axis" x="{{ $padL - 10 }}" y="{{ $gy + 4 }}" text-anchor="end">{{ $fmt($step * $t) }}</text>
        @endfor

        {{-- Label sumbu X: dihitung dari titik terakhir supaya label terbaru selalu tampil dan tidak bertabrakan --}}
        @foreach ($labels as $i => $label)
            @if (($n - 1 - $i) % $every === 0)
                <text class="lc-axis" x="{{ $x($i) }}" y="{{ $H - 8 }}" text-anchor="middle">{{ $label }}</text>
            @endif
        @endforeach

        {{-- Area dan garis --}}
        @foreach ($series->reverse() as $s)
            @php
                $pts = collect($s['values'])->values()->map(fn ($v, $i) => round($x($i), 1).' '.round($y((float) $v), 1));
                $line = 'M'.$pts->implode(' L');
            @endphp
            @if ($s['area'] && $n > 1)
                <path class="lc-area tone-{{ $s['color'] }}" d="{{ $line }} L{{ round($x($n - 1), 1) }} {{ $base }} L{{ $padL }} {{ $base }} Z" />
            @endif
            <path class="lc-line tone-{{ $s['color'] }} {{ $s['dashed'] ? 'is-dashed' : '' }}" d="{{ $line }}" />
        @endforeach

        {{-- Titik terakhir seri utama + angkanya --}}
        @if ($n > 0 && $series->isNotEmpty())
            @php
                $main = $series->first();
                $lv = (float) end($main['values']);
            @endphp
            <circle class="lc-dot tone-{{ $main['color'] }}" cx="{{ $x($n - 1) }}" cy="{{ $y($lv) }}" r="5" />
            <text class="lc-value" x="{{ $x($n - 1) - 10 }}" y="{{ max($padT + 4, $y($lv) - 12) }}" text-anchor="end">{{ $fmt($lv) }}</text>
        @endif

        {{-- Area sentuh per titik: garis bantu vertikal + titik + tooltip --}}
        @foreach ($labels as $i => $label)
            @php
                $left = $i === 0 ? $padL : ($x($i - 1) + $x($i)) / 2;
                $right = $i === $n - 1 ? $W - $padR : ($x($i) + $x($i + 1)) / 2;
                $tip = $tips[$i]."\n".$series->map(fn ($s) => $s['name'].': '.$fmt($s['values'][$i] ?? 0).($unit ? ' '.$unit : ''))->implode("\n");
            @endphp
            <g class="lc-hit" data-tip="{{ $tip }}">
                <rect x="{{ $left }}" y="{{ $padT }}" width="{{ max(1, $right - $left) }}" height="{{ $plotH }}" />
                <line class="lc-cross" x1="{{ $x($i) }}" x2="{{ $x($i) }}" y1="{{ $padT }}" y2="{{ $base }}" />
                @foreach ($series as $s)
                    <circle class="lc-mark tone-{{ $s['color'] }}" cx="{{ $x($i) }}" cy="{{ $y((float) ($s['values'][$i] ?? 0)) }}" r="5" />
                @endforeach
            </g>
        @endforeach
    </svg>
</div>
