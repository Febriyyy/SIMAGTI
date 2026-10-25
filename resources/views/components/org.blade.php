{{-- Tanda organisasi (perusahaan / kegiatan): kotak inisial berwarna tetap untuk nama yang sama. --}}
@props(['name'])
@php
    $words = collect(preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|Yayasan)\.?\s+/i', '', (string) $name))))->filter();
    $initials = mb_strtoupper($words->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
    $tone = crc32((string) $name) % 5;
@endphp
<span {{ $attributes->class(['org-mark', 'avatar-'.$tone]) }} aria-hidden="true">{{ $initials }}</span>
