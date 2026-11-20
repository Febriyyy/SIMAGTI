@extends('layouts.app')

@section('title', 'Tanda Tangan Sertifikat')

{{--
    Kaprodi: nama, jabatan, dan tanda tangan di sertifikat KP.
    Kiri: blok tanda tangan di atas lembar sertifikat (klik untuk unggah). Kanan: isian.
    Variabel: template, signature (data URI)
--}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Tanda Tangan Sertifikat</h1>
            <p class="page-sub">Diisi sekali, berlaku untuk semua sertifikat KP termasuk yang sudah terbit.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('kaprodi.ttd.update') }}" enctype="multipart/form-data" class="card ca ca-wide">
        @csrf
        @method('PUT')

        <div class="ca-paper">
            <label class="ca-sheet">
                <span class="ca-sig">
                    <span class="ca-sig-role">{{ $template->signer_title ?: 'Ketua Program Studi' }}</span>
                    <span class="ca-sig-ink">
                        <img src="{{ $signature ?? '' }}" alt="Tanda tangan kaprodi" data-live-sign-out="kaprodi" @if (! $signature) hidden @endif>
                        @unless ($signature)<span class="ca-sig-empty" data-live-sign-empty="kaprodi">Klik untuk unggah tanda tangan</span>@endunless
                    </span>
                    <span @class(['ca-sig-name', 'is-empty' => ! $template->signer_name]) data-live-name-out="kaprodi" data-empty="Nama Anda">{{ $template->signer_name ?: 'Nama Anda' }}</span>
                    <span class="ca-sig-sub">Program studi</span>
                </span>
                <span class="ca-hint"><x-ikon name="upload" :size="14" /> {{ $signature ? 'Ganti tanda tangan' : 'Unggah tanda tangan' }}</span>
                <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" class="sr-only" data-live-sign="kaprodi" aria-label="Tanda tangan kaprodi">
            </label>
        </div>

        <div class="ca-body">
            <div class="ca-title">
                <div>
                    <h2>Tanda tangan kampus</h2>
                    <p>Tampil di sisi kanan sertifikat</p>
                </div>
                <span @class(['ca-state', 'is-ok' => $signature && $template->signer_name])>{{ $signature && $template->signer_name ? 'Lengkap' : 'Belum lengkap' }}</span>
            </div>
            <div class="ca-fields">
                <div>
                    <label class="label" for="signer_name">Nama</label>
                    <input id="signer_name" type="text" name="signer_name" class="input" maxlength="150" required placeholder="Nama lengkap beserta gelar" value="{{ old('signer_name', $template->signer_name) }}" data-live-name="kaprodi">
                    @error('signer_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="signer_title">Jabatan</label>
                    <input id="signer_title" type="text" name="signer_title" class="input" maxlength="100" required value="{{ old('signer_title', $template->signer_title ?? 'Ketua Program Studi') }}">
                    @error('signer_title')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="ca-help">Klik lembar di samping untuk mengunggah tanda tangan. Disarankan 600 × 200 px, PNG berlatar transparan, maks. 1 MB.</p>
            @error('signature')<p class="field-error">{{ $message }}</p>@enderror
            <div class="ca-actions">
                @if ($signature)
                    <label class="ca-check"><input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan</label>
                @else
                    <span></span>
                @endif
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </form>

    @include('sertifikat._live')
@endsection
