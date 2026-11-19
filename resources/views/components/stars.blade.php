{{-- Bintang penilaian (tampilan saja): <x-stars :value="4.3" /> --}}
@props(['value' => 0, 'size' => 14])
<span {{ $attributes->class(['stars']) }} style="--stars: {{ max(0, min(5, (float) $value)) / 5 * 100 }}%; --star-size: {{ (int) $size }}px" role="img" aria-label="{{ \App\Support\Num::short((float) $value, 1) }} dari 5"></span>
