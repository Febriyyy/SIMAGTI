{{--
    Kartu angka ringkas. Satu gaya untuk semua ringkasan angka di aplikasi.
    Susunannya selalu sama: [ikon + label] / angka + satuan / satu baris keterangan.
    label  : judul pendek (satu baris)
    value  : angka utama; null = belum ada data (tampil sebagai teks $empty)
    unit   : satuan kecil di samping angka
    hint   : keterangan satu baris di bawah angka
    icon   : nama ikon
    tone   : blue (ungu aksen) | amber (perlu tindakan) | green (aman) | gray (informasi)
    href   : jika diisi, seluruh kartu bisa diklik (ditandai panah di pojok kanan atas)
    percent: jika diisi, baris keterangan diganti batang progres
--}}
@props(['label', 'value' => null, 'unit' => null, 'hint' => null, 'icon' => 'chart', 'tone' => 'blue', 'href' => null, 'percent' => null, 'empty' => 'Belum ada'])
@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['stat', 'stat-'.$tone, 'stat-link' => $href]) }}>
    <div class="stat-head">
        <span class="stat-icon"><x-icon :name="$icon" :size="16" /></span>
        <span class="stat-label">{{ $label }}</span>
        @if ($href)
            <span class="stat-go" aria-hidden="true"><x-icon name="arrow" :size="16" /></span>
        @endif
    </div>
    @if ($value === null || $value === '')
        <div class="stat-value is-empty">{{ $empty }}</div>
    @else
        <div class="stat-value">{{ $value }}@if ($unit)<small>{{ $unit }}</small>@endif</div>
    @endif
    <div class="stat-foot">
        @if ($percent !== null)
            <div class="stat-bar" role="presentation"><span style="width: {{ max(0, min(100, (int) $percent)) }}%"></span></div>
            <span class="stat-hint">{{ max(0, min(100, (int) $percent)) }}%</span>
        @else
            <span class="stat-hint" title="{{ $hint }}">{{ $hint ?? "\u{00A0}" }}</span>
        @endif
    </div>
</{{ $tag }}>
