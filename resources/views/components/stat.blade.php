{{--
    Kartu angka ringkas. Satu gaya untuk semua ringkasan angka di aplikasi.
    Susunannya: ikon berwarna + judul (+ panah bila bisa diklik) / angka besar / keterangan singkat.
    Di dalam .stat-grid kartu-kartu menjadi satu strip; warna ikon bergiliran (teal, kuning, biru, ungu) kecuali tone amber/green.

    label  : judul pendek (satu baris)
    value  : angka utama; null = belum ada data (tampil sebagai teks $empty)
    unit   : satuan kecil di samping angka
    hint   : keterangan singkat; untuk tone biru/abu hanya muncul sebagai tooltip (sedikit kata di layar)
    badge  : penanda kecil di depan keterangan (misalnya "+2"), opsional
    tone   : blue (aksen) | amber (perlu tindakan) | green (aman) | gray (informasi)
    href   : jika diisi, seluruh kartu bisa diklik
    percent: jika diisi, baris keterangan diganti batang progres
    icon   : nama ikon; bila kosong dipilih otomatis dari label
--}}
@props(['label', 'value' => null, 'unit' => null, 'hint' => null, 'badge' => null, 'icon' => null, 'tone' => 'blue', 'href' => null, 'percent' => null, 'empty' => 'Belum ada'])
@php
    $tag = $href ? 'a' : 'div';
    $l = mb_strtolower($label);
    $icon ??= match (true) {
        str_contains($l, 'perhatian') => 'alert',
        str_contains($l, 'task') => 'tasks',
        str_contains($l, 'rencana') => 'calendar',
        str_contains($l, 'goal') => 'flag',
        str_contains($l, 'nilai') || str_contains($l, 'dinilai') => 'award',
        str_contains($l, 'sisa') || str_contains($l, 'minggu') || str_contains($l, 'durasi') => 'timer',
        str_contains($l, 'menunggu') => 'clock',
        str_contains($l, 'validasi') || str_contains($l, 'verifikasi') || str_contains($l, 'konfirmasi') => 'verify',
        str_contains($l, 'surat') => 'mail',
        str_contains($l, 'akun') => 'users',
        str_contains($l, 'catatan') || str_contains($l, 'hari') => 'edit',
        str_contains($l, 'aktivitas') => 'activity',
        str_contains($l, 'pertanyaan') => 'approval',
        str_contains($l, 'mahasiswa') || str_contains($l, 'magang') => 'student',
        str_contains($l, 'kp') => 'placement',
        default => 'chart',
    };
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['stat', 'stat-'.$tone, 'stat-link' => $href]) }} @if ($hint) title="{{ $hint }}" @endif>
    <div class="stat-head">
        <span class="stat-icon" aria-hidden="true"><x-ikon :name="$icon" :size="18" /></span>
        <span class="stat-label">{{ $label }}</span>
        @if ($href)
            <span class="stat-go" aria-hidden="true"><x-ikon name="arrow-up-right" :size="16" /></span>
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
            <span class="stat-hint">{{ $hint ?? "\u{00A0}" }}</span>
        @endif
    </div>
</{{ $tag }}>
