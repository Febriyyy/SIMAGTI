@extends('layouts.app')

@section('title', 'Tanda Tangan Sertifikat')

{{--
    Kaprodi: nama, jabatan, dan tanda tangan, diisi langsung di blok kanan sertifikat ukuran penuh.
    Variabel: template, signature (data URI)
--}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Tanda Tangan Sertifikat</h1>
            <p class="page-sub">Klik blok tanda tangan di kanan sertifikat. Diisi sekali, berlaku untuk semua sertifikat KP.</p>
        </div>
    </div>

    <form id="f-ttd" method="POST" action="{{ route('kaprodi.ttd.update') }}" enctype="multipart/form-data" hidden>
        @csrf
        @method('PUT')
    </form>

    <section class="cst-studio">
        @include('sertifikat._stage', [
            'edit' => [],
            'company' => null,
            'left' => ['role' => 'Ketua Divisi', 'owner' => 'Diisi perusahaan', 'sub' => 'Perusahaan'],
            'right' => [
                'role' => $template->signer_title ?: 'Ketua Program Studi',
                'name' => $template->signer_name,
                'sign' => $signature,
                'sub' => \App\Support\Campus::get('campus.program'),
                'form' => 'f-ttd',
                'nameField' => 'signer_name',
                'titleField' => 'signer_title',
                'live' => 'kaprodi',
            ],
            'shapes' => ['sign'],
        ])

        <div class="cst-bar">
            <div class="cst-items">
                @include('sertifikat._bar-item', [
                    'icon' => 'edit', 'title' => 'Tanda tangan kampus', 'sub' => 'Ketua Program Studi',
                    'ok' => $signature && $template->signer_name, 'okLabel' => 'Lengkap', 'todoLabel' => 'Belum lengkap',
                    'messages' => [$errors->first('signer_name'), $errors->first('signer_title'), $errors->first('signature')],
                    'removeForm' => $signature ? 'f-ttd' : null, 'removeName' => 'remove_signature',
                    'deleteLabel' => 'Hapus tanda tangan', 'confirm' => 'Hapus tanda tangan kaprodi?',
                ])
            </div>
            <button type="submit" form="f-ttd" class="btn btn-primary cst-save">Simpan</button>
        </div>
    </section>
@endsection
