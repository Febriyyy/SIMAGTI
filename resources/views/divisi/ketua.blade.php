@extends('layouts.app')

@section('title', 'Data Sertifikat')

{{--
    Company Supervisor: logo perusahaan, nama dan tanda tangan ketua divisi untuk sertifikat KP.
    Satu kolom: logo perusahaan, lalu ketua divisi (nama dan tanda tangan) per divisi.
    Variabel: companies, divisions, signatures
--}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Data Sertifikat</h1>
            <p class="page-sub">Diisi sekali, dipakai di sertifikat KP semua mahasiswa magang Anda.</p>
        </div>
    </div>

    <div class="cs">
        <div class="cs-main">
            {{-- Logo perusahaan --}}
            <section class="card cs-card">
                @foreach ($companies as $company)
                    <?php $bag = $errors->getBag('logo'.$company->id); ?>
                    <div class="cs-row" id="perusahaan-{{ $company->id }}">
                        <div class="cs-label">
                            <h2>Logo perusahaan</h2>
                            <p>{{ $company->name }}</p>
                            <span @class(['cs-state', 'is-ok' => $company->logo_file_id])>{{ $company->logo_file_id ? 'Terpasang' : 'Belum ada' }}</span>
                        </div>
                        <div class="cs-field">
                            <form method="POST" action="{{ route('sertifikat.data.logo', $company) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <label @class(['cs-tile', 'has-file' => $company->logo_file_id])>
                                    @if ($company->logo_file_id)
                                        <img src="{{ route('logo.perusahaan', $company) }}?v={{ $company->logo_file_id }}" alt="Logo {{ $company->name }}">
                                        <span class="cs-tile-hover"><x-ikon name="upload" :size="16" /> Ganti</span>
                                    @else
                                        <x-ikon name="image" :size="22" />
                                        <b>Unggah logo</b>
                                    @endif
                                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah logo {{ $company->name }}">
                                </label>
                            </form>
                            <div class="cs-meta">
                                <small>Disarankan 500 × 500 px (persegi) atau 900 × 300 px (melebar). PNG/JPG, maks. 1 MB.</small>
                                @if ($company->logo_file_id)
                                    <form method="POST" action="{{ route('sertifikat.data.logo.destroy', $company) }}" onsubmit="return confirm('Hapus logo {{ $company->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cs-link">Hapus</button>
                                    </form>
                                @endif
                            </div>
                            @if ($bag->has('logo'))<p class="field-error">{{ $bag->first('logo') }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- Ketua divisi --}}
            @forelse ($divisions as $division)
                <?php $bag = $errors->getBag('divisi'.$division->id); $sig = $signatures[$division->id] ?? null; ?>
                <form method="POST" action="{{ route('sertifikat.data.divisi', $division) }}" enctype="multipart/form-data" class="card cs-card" id="divisi-{{ $division->id }}">
                    @csrf
                    @method('PUT')
                    <div class="cs-head">
                        <h2>Ketua Divisi {{ $division->name }}</h2>
                        <span @class(['cs-state', 'is-ok' => $division->head_name && $sig])>{{ $division->head_name && $sig ? 'Lengkap' : 'Belum lengkap' }}</span>
                    </div>
                    <div class="cs-row">
                        <div class="cs-label">
                            <h3>Nama</h3>
                            <p>Ditulis di bawah garis tanda tangan</p>
                        </div>
                        <div class="cs-field">
                            <label class="sr-only" for="dh-name-{{ $division->id }}">Nama ketua divisi {{ $division->name }}</label>
                            <input id="dh-name-{{ $division->id }}" type="text" name="head_name" class="input" maxlength="150" required placeholder="Nama lengkap beserta gelar" value="{{ old('head_name', $division->head_name) }}">
                            @if ($bag->has('head_name'))<p class="field-error">{{ $bag->first('head_name') }}</p>@endif
                        </div>
                    </div>
                    <div class="cs-row">
                        <div class="cs-label">
                            <h3>Tanda tangan</h3>
                            <p>Foto atau pindai di kertas putih</p>
                        </div>
                        <div class="cs-field">
                            @include('sertifikat._sign-pad', ['sign' => $sig, 'label' => 'Tanda tangan ketua divisi '.$division->name])
                            <div class="cs-meta">
                                <small>Disarankan 600 × 200 px, PNG berlatar transparan. Maks. 1 MB.</small>
                                @if ($sig)
                                    <label class="cs-check"><input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan</label>
                                @endif
                            </div>
                            @if ($bag->has('signature'))<p class="field-error">{{ $bag->first('signature') }}</p>@endif
                        </div>
                    </div>
                    <div class="cs-foot">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            @empty
                <section class="card">
                    <div class="empty">
                        <strong>Belum ada divisi</strong>
                        Ketua divisi bisa diisi setelah ada mahasiswa magang yang Anda supervisi di sebuah divisi.
                    </div>
                </section>
            @endforelse
        </div>
    </div>
@endsection
