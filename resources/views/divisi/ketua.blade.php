@extends('layouts.app')

@section('title', 'Data Sertifikat')

{{--
    Company Supervisor: logo perusahaan, nama dan tanda tangan ketua divisi untuk sertifikat KP.
    Tiap aset tampil di atas lembar sertifikat kecil yang bisa diklik untuk mengunggah.
    Variabel: companies, divisions, signatures
--}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Data Sertifikat</h1>
            <p class="page-sub">Diisi sekali, dipakai di sertifikat KP semua mahasiswa magang Anda.</p>
        </div>
    </div>

    <div class="ca-grid">
        @foreach ($companies as $company)
            <?php $bag = $errors->getBag('logo'.$company->id); ?>
            <section class="card ca" id="perusahaan-{{ $company->id }}">
                <form method="POST" action="{{ route('sertifikat.data.logo', $company) }}" enctype="multipart/form-data" class="ca-paper">
                    @csrf
                    @method('PUT')
                    <label class="ca-sheet">
                        @if ($company->logo_file_id)
                            <img class="ca-logo" src="{{ route('logo.perusahaan', $company) }}?v={{ $company->logo_file_id }}" alt="Logo {{ $company->name }}">
                        @else
                            <span class="ca-empty"><span class="ca-empty-icon"><x-ikon name="image" :size="20" /></span>Belum ada logo perusahaan</span>
                        @endif
                        <span class="ca-hint"><x-ikon name="upload" :size="14" /> {{ $company->logo_file_id ? 'Ganti logo' : 'Unggah logo' }}</span>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah logo {{ $company->name }}">
                    </label>
                </form>
                <div class="ca-body">
                    <div class="ca-title">
                        <div>
                            <h2>Logo perusahaan</h2>
                            <p>{{ $company->name }}</p>
                        </div>
                        <span @class(['ca-state', 'is-ok' => $company->logo_file_id])>{{ $company->logo_file_id ? 'Terpasang' : 'Belum ada' }}</span>
                    </div>
                    <p class="ca-help">Klik lembar di atas untuk mengunggah. Disarankan 500 × 500 px (persegi) atau 900 × 300 px (melebar), PNG atau JPG maks. 1 MB.</p>
                    @if ($bag->has('logo'))<p class="field-error">{{ $bag->first('logo') }}</p>@endif
                    @if ($company->logo_file_id)
                        <div class="ca-actions">
                            <span></span>
                            <form method="POST" action="{{ route('sertifikat.data.logo.destroy', $company) }}" onsubmit="return confirm('Hapus logo {{ $company->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ca-link">Hapus logo</button>
                            </form>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach

        @forelse ($divisions as $division)
            <?php $bag = $errors->getBag('divisi'.$division->id); $sig = $signatures[$division->id] ?? null; $k = 'divisi'.$division->id; ?>
            <form method="POST" action="{{ route('sertifikat.data.divisi', $division) }}" enctype="multipart/form-data" class="card ca" id="divisi-{{ $division->id }}">
                @csrf
                @method('PUT')
                <div class="ca-paper">
                    <label class="ca-sheet">
                        <span class="ca-sig">
                            <span class="ca-sig-role">Ketua Divisi {{ $division->name }}</span>
                            <span class="ca-sig-ink">
                                <img src="{{ $sig ?? '' }}" alt="Tanda tangan ketua divisi {{ $division->name }}" data-live-sign-out="{{ $k }}" @if (! $sig) hidden @endif>
                                @unless ($sig)<span class="ca-sig-empty" data-live-sign-empty="{{ $k }}">Klik untuk unggah tanda tangan</span>@endunless
                            </span>
                            <span @class(['ca-sig-name', 'is-empty' => ! $division->head_name]) data-live-name-out="{{ $k }}" data-empty="Nama ketua divisi">{{ $division->head_name ?: 'Nama ketua divisi' }}</span>
                            <span class="ca-sig-sub">{{ $division->company?->name }}</span>
                        </span>
                        <span class="ca-hint"><x-ikon name="upload" :size="14" /> {{ $sig ? 'Ganti tanda tangan' : 'Unggah tanda tangan' }}</span>
                        <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" class="sr-only" data-live-sign="{{ $k }}" aria-label="Tanda tangan ketua divisi {{ $division->name }}">
                    </label>
                </div>
                <div class="ca-body">
                    <div class="ca-title">
                        <div>
                            <h2>Ketua divisi</h2>
                            <p>Divisi {{ $division->name }}</p>
                        </div>
                        <span @class(['ca-state', 'is-ok' => $division->head_name && $sig])>{{ $division->head_name && $sig ? 'Lengkap' : 'Belum lengkap' }}</span>
                    </div>
                    <div>
                        <label class="label" for="dh-name-{{ $division->id }}">Nama ketua divisi</label>
                        <input id="dh-name-{{ $division->id }}" type="text" name="head_name" class="input" maxlength="150" required placeholder="Nama lengkap beserta gelar" value="{{ old('head_name', $division->head_name) }}" data-live-name="{{ $k }}">
                        @if ($bag->has('head_name'))<p class="field-error">{{ $bag->first('head_name') }}</p>@endif
                    </div>
                    <p class="ca-help">Klik lembar di atas untuk mengunggah tanda tangan. Disarankan 600 × 200 px, PNG berlatar transparan, maks. 1 MB.</p>
                    @if ($bag->has('signature'))<p class="field-error">{{ $bag->first('signature') }}</p>@endif
                    <div class="ca-actions">
                        @if ($sig)
                            <label class="ca-check"><input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan</label>
                        @else
                            <span></span>
                        @endif
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        @empty
            <section class="card ca">
                <div class="empty">
                    <strong>Belum ada divisi</strong>
                    Ketua divisi bisa diisi setelah ada mahasiswa magang yang Anda supervisi di sebuah divisi.
                </div>
            </section>
        @endforelse
    </div>

    @include('sertifikat._live')
@endsection
