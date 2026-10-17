{{--
    Kartu angka ringkas. Satu gaya untuk semua ringkasan angka di aplikasi (acuan: gaya "Donezo").
    Susunannya selalu sama: judul + tombol panah (jika bisa diklik) / angka besar / satu baris keterangan.
    Kartu pertama di setiap .stat-grid otomatis menjadi kartu sorotan (latar hijau tua).

    label  : judul pendek (satu baris)
    value  : angka utama; null = belum ada data (tampil sebagai teks $empty)
    unit   : satuan kecil di samping angka
    hint   : keterangan satu baris di bawah angka
    badge  : penanda kecil di depan keterangan (misalnya "+2"), opsional
    tone   : blue (aksen) | amber (perlu tindakan) | green (aman) | gray (informasi) — mewarnai keterangan
    href   : jika diisi, seluruh kartu bisa diklik (ditandai tombol panah melingkar)
    percent: jika diisi, baris keterangan diganti batang progres
    icon   : tidak ditampilkan lagi; tetap diterima supaya pemanggil lama tidak perlu diubah
--}}
@props(['label', 'value' => null, 'unit' => null, 'hint' => null, 'badge' => null, 'icon' => null, 'tone' => 'blue', 'href' => null, 'percent' => null, 'empty' => 'Belum ada'])
@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['stat', 'stat-'.$tone, 'stat-link' => $href]) }}>
    <div class="stat-head">
        <span class="stat-label">{{ $label }}</span>
        @if ($href)
            <span class="stat-go" aria-hidden="true"><x-icon name="arrow-up-right" :size="16" /></span>
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
            @if ($badge !== null)<span class="stat-badge">{{ $badge }}</span>@endif
            <span class="stat-hint" title="{{ $hint }}">{{ $hint ?? "\u{00A0}" }}</span>
        @endif
    </div>
</{{ $tag }}>
