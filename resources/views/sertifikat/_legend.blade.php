{{--
    Legenda ukuran gambar di panggung studio sertifikat, digambar sesuai proporsinya.
    Variabel: shapes, daftar dari 'square' (logo persegi), 'wide' (logo melebar), 'sign' (tanda tangan)
--}}
<?php
    $legend = [
        'square' => ['500 × 500 px', 'Logo persegi'],
        'wide' => ['900 × 300 px', 'Logo melebar'],
        'sign' => ['600 × 200 px', 'Tanda tangan, latar transparan'],
    ];
?>
<div class="cst-legend">
    <ul>
        @foreach ($shapes as $shape)
            <li>
                <span class="cst-glyph is-{{ $shape }}" aria-hidden="true">
                    @if ($shape === 'sign')
                        <svg viewBox="0 0 60 20"><path d="M3 14c5-9 8-9 9-2s3 6 6-2 5-7 6 0 4 5 8-3 5-2 6 3 6 2 9-4" /></svg>
                    @endif
                </span>
                <span class="cst-legend-text"><b>{{ $legend[$shape][0] }}</b>{{ $legend[$shape][1] }}</span>
            </li>
        @endforeach
    </ul>
    <p>PNG atau JPG, maks. 1 MB. Gambar besar otomatis dipaskan.</p>
</div>
