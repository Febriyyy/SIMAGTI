@extends('layouts.app')

@section('title', 'Tanda Tangan Sertifikat')

{{-- Kaprodi: nama, jabatan, dan tanda tangan di sertifikat KP. Variabel: template, signature (data URI) --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Tanda Tangan Sertifikat</h1>
            <p class="page-sub">Tampil sebagai penanda tangan kampus di semua sertifikat KP.</p>
        </div>
    </div>

    <section class="card dh-card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Penanda tangan kampus</h2>
                <p class="card-sub">Perubahan langsung berlaku, termasuk untuk sertifikat yang sudah terbit.</p>
            </div>
            <x-chip :tone="$signature ? 'green' : 'amber'">{{ $signature ? 'Lengkap' : 'Tanda tangan belum ada' }}</x-chip>
        </div>
        <form method="POST" action="{{ route('kaprodi.ttd.update') }}" enctype="multipart/form-data" class="card-body dh-form">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="field">
                    <label class="label" for="signer_name">Nama</label>
                    <input id="signer_name" type="text" name="signer_name" class="input" maxlength="150" required placeholder="Nama lengkap beserta gelar" value="{{ old('signer_name', $template->signer_name) }}">
                    @error('signer_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label class="label" for="signer_title">Jabatan</label>
                    <input id="signer_title" type="text" name="signer_title" class="input" maxlength="100" required value="{{ old('signer_title', $template->signer_title ?? 'Ketua Program Studi') }}">
                    @error('signer_title')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="field">
                <span class="label">Tanda tangan</span>
                <div class="dh-sign">
                    <div class="dh-sign-pad">
                        @if ($signature)
                            <img src="{{ $signature }}" alt="Tanda tangan {{ $template->signer_name }}">
                        @else
                            <span>Belum ada tanda tangan</span>
                        @endif
                        <i aria-hidden="true"></i>
                    </div>
                    <div class="dh-sign-input">
                        <input type="file" name="signature" class="input" accept="image/png,image/jpeg,image/webp" aria-label="Berkas tanda tangan">
                        <p class="hint">Foto atau pindai tanda tangan di kertas putih. PNG berlatar transparan paling rapi. Maksimal 1 MB.</p>
                        @if ($signature)
                            <label class="check"><input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan saat disimpan</label>
                        @endif
                        @error('signature')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="row-actions">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </section>
@endsection
