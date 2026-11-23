@extends('layouts.app')

@section('title', 'Identitas Kampus')

{{-- Admin: logo kampus dan logo fakultas, diisi langsung di atas sertifikat ukuran penuh. --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Identitas Kampus</h1>
            <p class="page-sub">Klik kotak logo di sertifikat untuk mengunggah. Diisi sekali, tampil di semua sertifikat KP.</p>
        </div>
    </div>

    @foreach (\App\Support\Logos::INSTITUTIONS as $kind => [$key, $label])
        <form id="f-logo-{{ $kind }}" method="POST" action="{{ route('admin.kampus.update', $kind) }}" enctype="multipart/form-data" hidden>
            @csrf
            @method('PUT')
        </form>
    @endforeach

    <section class="cst-studio">
        @include('sertifikat._stage', [
            'edit' => ['kampus' => 'f-logo-kampus', 'fakultas' => 'f-logo-fakultas'],
            'company' => null,
            'left' => ['role' => 'Ketua Divisi', 'owner' => 'Diisi perusahaan', 'sub' => 'Perusahaan'],
            'right' => ['role' => 'Ketua Program Studi', 'owner' => 'Diisi kaprodi', 'sub' => $campus['program']],
        ])

        <div class="cst-bar">
            @foreach (\App\Support\Logos::INSTITUTIONS as $kind => [$key, $label])
                <?php $logo = $logos[$kind]; $bag = $errors->getBag('logo_'.$kind); ?>
                <div class="cst-item" id="logo-{{ $kind }}">
                    <span @class(['cst-dot', 'is-ok' => $logo])></span>
                    <div>
                        <strong>{{ $label }}</strong>
                        <span>{{ $kind === 'kampus' ? $campus['university'] : $campus['faculty'] }}</span>
                        @if ($bag->has('logo'))<p class="field-error">{{ $bag->first('logo') }}</p>@endif
                    </div>
                    @if ($logo)
                        <form method="POST" action="{{ route('admin.kampus.destroy', $kind) }}" onsubmit="return confirm('Hapus {{ mb_strtolower($label) }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cst-link">Hapus</button>
                        </form>
                    @endif
                </div>
            @endforeach
            <p class="cst-guide">Disarankan 500 × 500 px (persegi) atau 900 × 300 px (melebar), PNG atau JPG maks. 1 MB. Gambar besar otomatis dipaskan.</p>
        </div>
    </section>
@endsection
