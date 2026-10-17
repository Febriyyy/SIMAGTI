@props(['user', 'small' => false])
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter(fn ($w) => preg_match('/^[\p{L}]/u', $w))
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
@endphp
<span {{ $attributes->class(['avatar', 'avatar-sm' => $small]) }} aria-hidden="true">{{ $initials }}</span>
