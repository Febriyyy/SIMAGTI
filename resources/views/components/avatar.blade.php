@props(['user', 'small' => false])
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter(fn ($w) => preg_match('/^[\p{L}]/u', $w) && ! str_ends_with($w, '.'))
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
    // Warna tetap untuk orang yang sama, supaya mudah dikenali di semua halaman.
    $tone = crc32($user->name) % 5;
@endphp
<span {{ $attributes->class(['avatar', 'avatar-'.$tone, 'avatar-sm' => $small]) }} aria-hidden="true">{{ $initials ?: '?' }}</span>
