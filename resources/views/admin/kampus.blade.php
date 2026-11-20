@extends('layouts.app')

@section('title', 'Identitas Kampus')

{{-- Admin: logo kampus untuk sertifikat KP. Nama kampus, fakultas, prodi, dan kota diambil dari konfigurasi. --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Identitas Kampus</h1>
            <p class="page-sub">Logo dan nama kampus yang tampil di sertifikat KP.</p>
        </div>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Logo kampus</h2>
                        <p class="card-sub">PNG, JPG, atau WebP, maksimal 1 MB. Latar transparan paling rapi.</p>
                    </div>
                </div>
                <div class="card-body logo-edit">
                    <div class="logo-preview">
                        @if ($logo)
                            <img src="{{ route('logo.kampus') }}?v={{ $logo->id }}" alt="Logo kampus saat ini">
                        @else
                            <span>Belum ada logo</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.kampus.update') }}" enctype="multipart/form-data" class="logo-form">
                        @csrf
                        @method('PUT')
                        <label class="btn btn-primary">
                            <x-ikon name="upload" :size="16" /> {{ $logo ? 'Ganti logo' : 'Unggah logo' }}
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                        </label>
                        @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                    </form>
                    @if ($logo)
                        <form method="POST" action="{{ route('admin.kampus.destroy') }}" onsubmit="return confirm('Hapus logo kampus? Sertifikat akan memakai logo SIMAGTI.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost">Hapus logo</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Tertulis di sertifikat</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <div><dt>Universitas</dt><dd>{{ $campus['university'] }}</dd></div>
                        <div><dt>Fakultas</dt><dd>{{ $campus['faculty'] }}</dd></div>
                        <div><dt>Program studi</dt><dd>{{ $campus['program'] }}</dd></div>
                        <div><dt>Kota</dt><dd>{{ $campus['city'] }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
@endsection
