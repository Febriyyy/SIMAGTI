@extends('layouts.app')

@section('title', 'Tanda Tangan Sertifikat')

{{-- Kaprodi: nama, jabatan, dan tanda tangan di sertifikat KP. Variabel: template, signature (data URI) --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Tanda Tangan Sertifikat</h1>
            <p class="page-sub">Diisi sekali, berlaku untuk semua sertifikat KP termasuk yang sudah terbit.</p>
        </div>
    </div>

    <div class="cs">
        <form method="POST" action="{{ route('kaprodi.ttd.update') }}" enctype="multipart/form-data" class="card cs-card">
            @csrf
            @method('PUT')

            <div class="cs-row">
                <div class="cs-label">
                    <h2>Nama</h2>
                    <p>Ditulis di bawah garis tanda tangan</p>
                </div>
                <div class="cs-field">
                    <label class="sr-only" for="signer_name">Nama</label>
                    <input id="signer_name" type="text" name="signer_name" class="input" maxlength="150" required placeholder="Nama lengkap beserta gelar" value="{{ old('signer_name', $template->signer_name) }}">
                    @error('signer_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="cs-row">
                <div class="cs-label">
                    <h2>Jabatan</h2>
                    <p>Ditulis di atas tanda tangan</p>
                </div>
                <div class="cs-field">
                    <label class="sr-only" for="signer_title">Jabatan</label>
                    <input id="signer_title" type="text" name="signer_title" class="input" maxlength="100" required value="{{ old('signer_title', $template->signer_title ?? 'Ketua Program Studi') }}">
                    @error('signer_title')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="cs-row">
                <div class="cs-label">
                    <h2>Tanda tangan</h2>
                    <p>Foto atau pindai di kertas putih</p>
                    <span @class(['cs-state', 'is-ok' => $signature])>{{ $signature ? 'Terpasang' : 'Belum ada' }}</span>
                </div>
                <div class="cs-field">
                    @include('sertifikat._sign-pad', ['sign' => $signature, 'label' => 'Tanda tangan kaprodi'])
                    <div class="cs-meta">
                        <small>Disarankan 600 × 200 px, PNG berlatar transparan. Maks. 1 MB.</small>
                        @if ($signature)
                            <label class="cs-check"><input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan</label>
                        @endif
                    </div>
                    @error('signature')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="cs-foot">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@endsection
