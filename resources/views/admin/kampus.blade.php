@extends('layouts.app')

@section('title', 'Identitas Kampus')

{{-- Admin: logo kampus dan logo fakultas untuk sertifikat KP. Nama kampus, fakultas, prodi, dan kota diambil dari konfigurasi. --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Identitas Kampus</h1>
            <p class="page-sub">Logo dan nama kampus yang tampil di sertifikat KP.</p>
        </div>
    </div>

    <div class="layout">
        <div class="col">
            @foreach (\App\Support\Logos::INSTITUTIONS as $kind => [$key, $label])
                <?php $logo = $logos[$kind]; $bag = $errors->getBag('logo_'.$kind); ?>
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">{{ $label }}</h2>
                            <p class="card-sub">{{ $kind === 'kampus' ? $campus['university'] : $campus['faculty'] }} · PNG, JPG, atau WebP, maksimal 1 MB</p>
                        </div>
                    </div>
                    <div class="card-body logo-edit">
                        <div class="logo-preview">
                            @if ($logo)
                                <img src="{{ route('logo.'.$kind) }}?v={{ $logo->id }}" alt="{{ $label }} saat ini">
                            @else
                                <span>Belum ada logo</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.kampus.update', $kind) }}" enctype="multipart/form-data" class="logo-form">
                            @csrf
                            @method('PUT')
                            <label class="btn btn-primary">
                                <x-ikon name="upload" :size="16" /> {{ $logo ? 'Ganti logo' : 'Unggah logo' }}
                                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah {{ mb_strtolower($label) }}">
                            </label>
                            @if ($bag->has('logo'))<p class="field-error">{{ $bag->first('logo') }}</p>@endif
                        </form>
                        @if ($logo)
                            <form method="POST" action="{{ route('admin.kampus.destroy', $kind) }}" onsubmit="return confirm('Hapus {{ mb_strtolower($label) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost">Hapus logo</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endforeach
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
                    <p class="hint">Tanda tangan Ketua Program Studi diunggah kaprodi sendiri di menu Tanda Tangan Sertifikat.</p>
                </div>
            </section>
        </div>
    </div>
@endsection
