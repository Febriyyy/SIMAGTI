{{-- Panduan ukuran gambar di bilah bawah studio sertifikat. Variabel: rows [label => keterangan] --}}
<dl class="cst-spec">
    @foreach ($rows as $label => $value)
        <div>
            <dt>{{ $label }}</dt>
            <dd>{{ $value }}</dd>
        </div>
    @endforeach
    <div>
        <dt>Berkas</dt>
        <dd>PNG atau JPG, maks. 1 MB</dd>
    </div>
</dl>
