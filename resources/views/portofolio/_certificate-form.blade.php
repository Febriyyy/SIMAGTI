{{-- Formulir sertifikat pelatihan (tambah/ubah). Variabel: id, action, certificate (opsional), bag --}}
<?php
    $e = $errors->getBag($bag);
    $v = fn (string $key, $default = null) => $e->any() ? old($key, $default) : $default;
?>
<dialog id="{{ $id }}" class="dialog" @if ($e->any()) data-autoopen @endif>
    <form method="POST" action="{{ $action }}" class="dialog-body" enctype="multipart/form-data">
        @csrf
        <div class="dialog-head">
            <div>
                <h2 class="card-title">{{ $certificate ? 'Ubah sertifikat' : 'Tambah sertifikat' }}</h2>
                <p class="card-sub">Bootcamp, kursus, pelatihan, atau lomba</p>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
        </div>
        <div class="form-grid pf-form">
            <div class="span-all">
                <label class="label" for="{{ $id }}-name">Nama pelatihan</label>
                <input id="{{ $id }}-name" name="name" class="input" maxlength="150" required value="{{ $v('name', $certificate?->name) }}" placeholder="Bootcamp Fullstack Web Developer">
                @if ($e->has('name'))<p class="field-error">{{ $e->first('name') }}</p>@endif
            </div>
            <div>
                <label class="label" for="{{ $id }}-issuer">Penyelenggara</label>
                <input id="{{ $id }}-issuer" name="issuer" class="input" maxlength="120" required value="{{ $v('issuer', $certificate?->issuer) }}" placeholder="Dicoding">
                @if ($e->has('issuer'))<p class="field-error">{{ $e->first('issuer') }}</p>@endif
            </div>
            <div>
                <label class="label" for="{{ $id }}-date">Tanggal terbit</label>
                <input id="{{ $id }}-date" name="issued_on" type="date" class="input" max="{{ today()->toDateString() }}" value="{{ $v('issued_on', $certificate?->issued_on?->toDateString()) }}">
                @if ($e->has('issued_on'))<p class="field-error">{{ $e->first('issued_on') }}</p>@endif
            </div>
            @include('portofolio._links-field', ['id' => $id, 'e' => $e, 'links' => $e->any() ? array_values(array_filter((array) old('links', []))) : array_column($certificate?->linkList() ?? [], 'url'), 'hint' => 'Halaman kredensial atau verifikasi.'])
            <div class="span-all">
                <label class="label" for="{{ $id }}-file">Berkas sertifikat</label>
                <input id="{{ $id }}-file" name="file" type="file" class="input" accept="application/pdf,image/jpeg,image/png">
                <p class="hint">{{ $certificate?->file ? 'Kosongkan bila tidak diganti. ' : '' }}PDF, JPG, atau PNG, maks. 5 MB.</p>
                @if ($e->has('file'))<p class="field-error">{{ $e->first('file') }}</p>@endif
            </div>
        </div>
        <div class="dialog-foot row-actions">
            <button type="button" class="btn" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">{{ $certificate ? 'Simpan sertifikat' : 'Tambah sertifikat' }}</button>
        </div>
    </form>
</dialog>
