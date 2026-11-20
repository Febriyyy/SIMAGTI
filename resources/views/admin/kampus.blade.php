@extends('layouts.app')

@section('title', 'Identitas Kampus')

{{-- Admin: logo kampus dan logo fakultas untuk sertifikat KP, ditampilkan di atas lembar sertifikat kecil. --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Identitas Kampus</h1>
            <p class="page-sub">Diunggah sekali, tampil di bagian atas semua sertifikat KP.</p>
        </div>
    </div>

    <div class="ca-grid">
        @foreach (\App\Support\Logos::INSTITUTIONS as $kind => [$key, $label])
            <?php $logo = $logos[$kind]; $bag = $errors->getBag('logo_'.$kind); ?>
            <section class="card ca" id="logo-{{ $kind }}">
                <form method="POST" action="{{ route('admin.kampus.update', $kind) }}" enctype="multipart/form-data" class="ca-paper">
                    @csrf
                    @method('PUT')
                    <label class="ca-sheet">
                        @if ($logo)
                            <img class="ca-logo" src="{{ route('logo.'.$kind) }}?v={{ $logo->id }}" alt="{{ $label }} saat ini">
                        @else
                            <span class="ca-empty"><span class="ca-empty-icon"><x-ikon name="image" :size="20" /></span>Belum ada {{ mb_strtolower($label) }}</span>
                        @endif
                        <span class="ca-hint"><x-ikon name="upload" :size="14" /> {{ $logo ? 'Ganti logo' : 'Unggah logo' }}</span>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah {{ mb_strtolower($label) }}">
                    </label>
                </form>
                <div class="ca-body">
                    <div class="ca-title">
                        <div>
                            <h2>{{ $label }}</h2>
                            <p>{{ $kind === 'kampus' ? $campus['university'] : $campus['faculty'] }}</p>
                        </div>
                        <span @class(['ca-state', 'is-ok' => $logo])>{{ $logo ? 'Terpasang' : 'Belum ada' }}</span>
                    </div>
                    <p class="ca-help">Klik lembar di atas untuk mengunggah. Disarankan 500 × 500 px (persegi) atau 900 × 300 px (melebar), PNG atau JPG maks. 1 MB.</p>
                    @if ($bag->has('logo'))<p class="field-error">{{ $bag->first('logo') }}</p>@endif
                    @if ($logo)
                        <div class="ca-actions">
                            <span></span>
                            <form method="POST" action="{{ route('admin.kampus.destroy', $kind) }}" onsubmit="return confirm('Hapus {{ mb_strtolower($label) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ca-link">Hapus logo</button>
                            </form>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <dl class="card ca-strip">
        <div><dt>Universitas</dt><dd>{{ $campus['university'] }}</dd></div>
        <div><dt>Fakultas</dt><dd>{{ $campus['faculty'] }}</dd></div>
        <div><dt>Program studi</dt><dd>{{ $campus['program'] }}</dd></div>
        <div><dt>Kota</dt><dd>{{ $campus['city'] }}</dd></div>
        <p class="ca-strip-note">Teks identitas diambil dari konfigurasi aplikasi. Tanda tangan Ketua Program Studi diisi kaprodi, logo perusahaan dan ketua divisi diisi Company Supervisor.</p>
    </dl>
@endsection
