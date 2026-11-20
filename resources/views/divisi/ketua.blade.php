@extends('layouts.app')

@section('title', 'Data Sertifikat')

{{-- Company Supervisor: logo perusahaan, nama dan tanda tangan ketua divisi untuk sertifikat KP. Variabel: companies, divisions, signatures --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Data Sertifikat</h1>
            <p class="page-sub">Logo perusahaan serta nama dan tanda tangan ketua divisi tampil di sertifikat KP mahasiswa magang Anda.</p>
        </div>
    </div>

    @foreach ($companies as $company)
        <?php $bag = $errors->getBag('logo'.$company->id); ?>
        <section class="card" id="perusahaan-{{ $company->id }}">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Logo {{ $company->name }}</h2>
                    <p class="card-sub">PNG, JPG, atau WebP, maksimal 1 MB. Latar transparan paling rapi.</p>
                </div>
                <x-chip :tone="$company->logo_file_id ? 'green' : 'amber'">{{ $company->logo_file_id ? 'Lengkap' : 'Belum ada logo' }}</x-chip>
            </div>
            <div class="card-body logo-edit">
                <div class="logo-preview">
                    @if ($company->logo_file_id)
                        <img src="{{ route('logo.perusahaan', $company) }}?v={{ $company->logo_file_id }}" alt="Logo {{ $company->name }}">
                    @else
                        <span>Belum ada logo</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('sertifikat.data.logo', $company) }}" enctype="multipart/form-data" class="logo-form">
                    @csrf
                    @method('PUT')
                    <label class="btn btn-primary">
                        <x-ikon name="upload" :size="16" /> {{ $company->logo_file_id ? 'Ganti logo' : 'Unggah logo' }}
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah logo {{ $company->name }}">
                    </label>
                    @if ($bag->has('logo'))<p class="field-error">{{ $bag->first('logo') }}</p>@endif
                </form>
                @if ($company->logo_file_id)
                    <form method="POST" action="{{ route('sertifikat.data.logo.destroy', $company) }}" onsubmit="return confirm('Hapus logo {{ $company->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost">Hapus logo</button>
                    </form>
                @endif
            </div>
        </section>
    @endforeach

    @if ($divisions->isEmpty())
        <section class="card">
            <div class="empty">
                <strong>Belum ada divisi</strong>
                Ketua divisi bisa diisi setelah ada mahasiswa magang yang Anda supervisi di sebuah divisi.
            </div>
        </section>
    @else
        <div class="dh-list">
            @foreach ($divisions as $division)
                <?php $bag = $errors->getBag('divisi'.$division->id); $sig = $signatures[$division->id] ?? null; ?>
                <section class="card dh-card" id="divisi-{{ $division->id }}">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Divisi {{ $division->name }}</h2>
                            <p class="card-sub">{{ $division->company?->name }}</p>
                        </div>
                        @if ($division->head_name && $sig)
                            <x-chip tone="green">Lengkap</x-chip>
                        @else
                            <x-chip tone="amber">{{ $division->head_name ? 'Tanda tangan belum ada' : 'Belum diisi' }}</x-chip>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('sertifikat.data.divisi', $division) }}" enctype="multipart/form-data" class="card-body dh-form">
                        @csrf
                        @method('PUT')
                        <div class="field">
                            <label class="label" for="dh-name-{{ $division->id }}">Nama ketua divisi</label>
                            <input id="dh-name-{{ $division->id }}" type="text" name="head_name" class="input" maxlength="150" required placeholder="Nama lengkap beserta gelar" value="{{ old('head_name', $division->head_name) }}">
                            @if ($bag->has('head_name'))<p class="field-error">{{ $bag->first('head_name') }}</p>@endif
                        </div>

                        <div class="field">
                            <span class="label">Tanda tangan</span>
                            <div class="dh-sign">
                                <div class="dh-sign-pad">
                                    @if ($sig)
                                        <img src="{{ $sig }}" alt="Tanda tangan ketua divisi {{ $division->name }}">
                                    @else
                                        <span>Belum ada tanda tangan</span>
                                    @endif
                                    <i aria-hidden="true"></i>
                                </div>
                                <div class="dh-sign-input">
                                    <input type="file" name="signature" class="input" accept="image/png,image/jpeg,image/webp" aria-label="Berkas tanda tangan divisi {{ $division->name }}">
                                    <p class="hint">Foto atau pindai tanda tangan di kertas putih. PNG berlatar transparan paling rapi. Maksimal 1 MB.</p>
                                    @if ($sig)
                                        <label class="check"><input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan saat disimpan</label>
                                    @endif
                                    @if ($bag->has('signature'))<p class="field-error">{{ $bag->first('signature') }}</p>@endif
                                </div>
                            </div>
                        </div>

                        <div class="row-actions">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </section>
            @endforeach
        </div>
    @endif
@endsection
