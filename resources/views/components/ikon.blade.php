{{--
    Ikon aplikasi: <x-ikon name="tasks" />, <x-ikon name="tasks" fill /> untuk versi terisi.

    Sengaja bernama <x-ikon>, bukan <x-icon>: paket blade-icons sudah memakai nama <x-icon> untuk komponennya sendiri.
    Peta nama ikon ada di app/Support/Icons.php.

    Yang tampil: Google Material Symbols (Rounded). Selama font belum siap atau gagal dimuat,
    yang tampil adalah ikon cadangan (Heroicons jika paketnya terpasang, atau garis sederhana bawaan).
    Pengaturnya ada di layouts/app.blade.php (kelas "ms-ready" pada <html>).
--}}
@props(['name', 'size' => 18, 'fill' => false])
@php
    $symbol = \App\Support\Icons::material($name);
    $svg = null;
    if (isset(\App\Support\Icons::HEROICONS[$name]) && function_exists('svg') && class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
        try {
            $svg = svg('heroicon-o-'.\App\Support\Icons::HEROICONS[$name], 'ikon-fallback', ['width' => $size, 'height' => $size, 'aria-hidden' => 'true'])->toHtml();
        } catch (\Throwable) {
            $svg = null;
        }
    }
@endphp
<span {{ $attributes->class(['icon', 'ikon', 'is-fill' => $fill]) }} style="--ikon: {{ (int) $size }}px" aria-hidden="true">
    @if ($symbol)<span class="ms">{{ $symbol }}</span>@endif
    @if ($svg){!! $svg !!}@else<svg class="ikon-fallback" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! \App\Support\Icons::PATHS[$name] ?? '' !!}</svg>@endif
</span>
