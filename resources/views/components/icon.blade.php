{{--
    Ikon aplikasi. Memakai Heroicons (outline, 24px) dari paket resmi blade-ui-kit/blade-heroicons.
    Pasang sekali:  composer require blade-ui-kit/blade-heroicons

    Nama di kiri dipakai di semua tampilan (<x-icon name="tasks" />); nama di kanan = nama ikon Heroicons.
    Mengganti ikon cukup mengubah peta ini, tidak perlu menyentuh halaman.

    Sebelum paket terpasang, komponen memakai ikon cadangan sederhana di bawah supaya halaman tetap tampil.
--}}
@props(['name', 'size' => 18])
@php
    $heroicons = [
        'home' => 'home',
        'tasks' => 'clipboard-document-check',
        'activity' => 'presentation-chart-line',
        'logout' => 'arrow-right-start-on-rectangle',
        'link' => 'link',
        'video' => 'video-camera',
        'plus' => 'plus',
        'calendar' => 'calendar-days',
        'clock' => 'clock',
        'users' => 'user-group',
        'building' => 'building-office-2',
        'chat' => 'chat-bubble-left-ellipsis',
        'back' => 'chevron-left',
        'paperclip' => 'paper-clip',
        'arrow' => 'arrow-right',
        'arrow-up-right' => 'arrow-up-right',
        'check' => 'check-circle',
        'award' => 'trophy',
        'chart' => 'chart-bar',
        'alert' => 'exclamation-triangle',
    ];

    $attributes = $attributes->merge(['width' => $size, 'height' => $size, 'aria-hidden' => 'true']);

    $svg = null;
    if (isset($heroicons[$name]) && function_exists('svg') && class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
        try {
            $svg = svg('heroicon-o-'.$heroicons[$name], 'icon', $attributes->getAttributes())->toHtml();
        } catch (\Throwable) {
            $svg = null; // nama ikon tidak ada di versi paket ini: pakai cadangan
        }
    }
@endphp
@if ($svg)
    {!! $svg !!}
@else
    @php
        // Cadangan: dipakai hanya jika paket Heroicons belum dipasang.
        $fallback = [
            'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
            'tasks' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
            'activity' => '<path d="M4 19h16M7 15v-4M12 15V7M17 15v-6"/>',
            'logout' => '<path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4"/><path d="M15 8l4 4-4 4M19 12H9"/>',
            'link' => '<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>',
            'video' => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3z"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20c.6-3.4 3-5.5 6-5.5s5.4 2.1 6 5.5M16 5a3.5 3.5 0 010 7"/>',
            'building' => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-4h4v4"/>',
            'chat' => '<path d="M4 5h16v11H9l-5 4z"/>',
            'back' => '<path d="M15 5l-7 7 7 7"/>',
            'paperclip' => '<path d="M20 11.5l-8 8a5 5 0 01-7-7l8-8a3.4 3.4 0 014.8 4.8l-8 8a1.7 1.7 0 01-2.4-2.4l7.4-7.4"/>',
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'arrow-up-right' => '<path d="M7 17L17 7M9 7h8v8"/>',
            'check' => '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
            'award' => '<path d="M8 4h8v5a4 4 0 01-8 0z"/><path d="M8 6H5v1a3 3 0 003 3M16 6h3v1a3 3 0 01-3 3M12 13v4M9 20h6"/>',
            'chart' => '<path d="M4 19h16M7 15v-4M12 15V7M17 15v-6"/>',
            'alert' => '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17v.5"/>',
        ];
    @endphp
    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>{!! $fallback[$name] ?? '' !!}</svg>
@endif
