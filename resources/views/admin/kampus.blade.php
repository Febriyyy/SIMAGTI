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
            <div class="cst-items">
                @foreach (\App\Support\Logos::INSTITUTIONS as $kind => [$key, $label])
                    @include('sertifikat._bar-item', [
                        'icon' => 'image', 'title' => $label, 'sub' => $kind === 'kampus' ? $campus['university'] : $campus['faculty'],
                        'ok' => (bool) $logos[$kind], 'okLabel' => 'Terpasang', 'todoLabel' => 'Belum ada',
                        'messages' => [$errors->getBag('logo_'.$kind)->first('logo')],
                        'delete' => $logos[$kind] ? route('admin.kampus.destroy', $kind) : null,
                        'deleteLabel' => 'Hapus '.mb_strtolower($label), 'confirm' => 'Hapus '.mb_strtolower($label).'?',
                    ])
                @endforeach
            </div>
            @include('sertifikat._bar-spec', ['rows' => ['Persegi' => '500 × 500 px', 'Melebar' => '900 × 300 px']])
        </div>
    </section>
@endsection
