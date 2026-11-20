@extends('layouts.app')

@section('title', 'Identitas Kampus')

{{-- Admin: logo kampus dan logo fakultas untuk sertifikat KP. Nama kampus, fakultas, prodi, dan kota diambil dari konfigurasi. --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Identitas Kampus</h1>
            <p class="page-sub">Diunggah sekali, tampil di bagian atas semua sertifikat KP.</p>
        </div>
    </div>

    <div class="cs">
        <section class="card cs-card">
            @foreach (\App\Support\Logos::INSTITUTIONS as $kind => [$key, $label])
                <?php $logo = $logos[$kind]; $bag = $errors->getBag('logo_'.$kind); ?>
                <div class="cs-row" id="logo-{{ $kind }}">
                    <div class="cs-label">
                        <h2>{{ $label }}</h2>
                        <p>{{ $kind === 'kampus' ? $campus['university'] : $campus['faculty'] }}</p>
                        <span @class(['cs-state', 'is-ok' => $logo])>{{ $logo ? 'Terpasang' : 'Belum ada' }}</span>
                    </div>
                    <div class="cs-field">
                        <form method="POST" action="{{ route('admin.kampus.update', $kind) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <label @class(['cs-tile', 'has-file' => $logo])>
                                @if ($logo)
                                    <img src="{{ route('logo.'.$kind) }}?v={{ $logo->id }}" alt="{{ $label }} saat ini">
                                    <span class="cs-tile-hover"><x-ikon name="upload" :size="16" /> Ganti</span>
                                @else
                                    <x-ikon name="image" :size="22" />
                                    <b>Unggah {{ mb_strtolower($label) }}</b>
                                @endif
                                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah {{ mb_strtolower($label) }}">
                            </label>
                        </form>
                        <div class="cs-meta">
                            <small>Disarankan 500 × 500 px (persegi) atau 900 × 300 px (melebar). PNG/JPG, maks. 1 MB.</small>
                            @if ($logo)
                                <form method="POST" action="{{ route('admin.kampus.destroy', $kind) }}" onsubmit="return confirm('Hapus {{ mb_strtolower($label) }}?')">
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

            <div class="cs-row">
                <div class="cs-label">
                    <h2>Teks identitas</h2>
                    <p>Diambil dari konfigurasi aplikasi</p>
                </div>
                <div class="cs-field">
                    <dl class="cs-facts">
                        <div><dt>Universitas</dt><dd>{{ $campus['university'] }}</dd></div>
                        <div><dt>Fakultas</dt><dd>{{ $campus['faculty'] }}</dd></div>
                        <div><dt>Program studi</dt><dd>{{ $campus['program'] }}</dd></div>
                        <div><dt>Kota</dt><dd>{{ $campus['city'] }}</dd></div>
                    </dl>
                </div>
            </div>
        </section>
    </div>
@endsection
