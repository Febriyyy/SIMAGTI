{{--
    Standar UI: informasi panjang (alasan, catatan, daftar, nama lengkap) disembunyikan di daftar
    dan baru tampil saat "Detail" dibuka. Tombol Detail di kanan, isinya selebar baris.
--}}
@props(['label' => 'Detail'])
<details {{ $attributes->class(['more']) }}>
    <summary class="more-btn">{{ $label }} <x-ikon name="expand" :size="16" /></summary>
    <div class="more-body">{{ $slot }}</div>
</details>
