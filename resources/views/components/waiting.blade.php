{{-- Umur antrean: berapa lama sebuah permintaan menunggu. Kuning setelah 3 hari, merah setelah 7 hari. --}}
@props(['since'])
@php
    $days = $since ? (int) $since->copy()->startOfDay()->diffInDays(now()->startOfDay()) : 0;
    $tone = $days >= 7 ? 'red' : ($days >= 3 ? 'amber' : 'gray');
@endphp
<span {{ $attributes->class(['waiting', 'waiting-'.$tone]) }} title="Menunggu sejak {{ $since?->locale('id')->translatedFormat('j F Y') }}">
    <x-ikon name="clock" :size="13" /> {{ $days === 0 ? 'Hari ini' : $days.' hari' }}
</span>
