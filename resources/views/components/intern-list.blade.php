{{-- Daftar mahasiswa pemantauan: kepala kolom + baris <x-intern-row>. Variabel: compact --}}
@props(['compact' => false])
<div class="slist-wrap">
    <ul {{ $attributes->class(['slist', 'slist-compact' => $compact]) }}>
        <li @class(['srow', 'srow-head', 'srow-compact' => $compact]) aria-hidden="true">
            <span>Mahasiswa</span>
            @unless ($compact)<span>Posisi</span>@endunless
            <span>Task</span>
            <span class="srow-num">{{ $compact ? 'Jam minggu ini' : 'Jam' }}</span>
            @unless ($compact)<span>Minggu</span><span>Silabus</span>@endunless
            <span></span>
        </li>
        {{ $slot }}
    </ul>
</div>
