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
        ])

        <div class="cst-bar">
            <div class="cst-item">
                <span @class(['cst-dot', 'is-ok' => $signature && $template->signer_name])></span>
                <div>
                    <strong>Tanda tangan kampus</strong>
                    <span>{{ $signature && $template->signer_name ? 'Lengkap' : 'Ketik nama dan unggah tanda tangan' }}</span>
                    @foreach (['signer_name', 'signer_title', 'signature'] as $f)
                        @error($f)<p class="field-error">{{ $message }}</p>@enderror
                    @endforeach
                </div>
            </div>
            <p class="cst-guide">Disarankan 600 × 200 px, PNG berlatar transparan, maks. 1 MB.</p>
            <div class="cst-actions">
                @if ($signature)
                    <label class="cst-check"><input type="checkbox" name="remove_signature" value="1" form="f-ttd"> Hapus tanda tangan</label>
                @endif
                <button type="submit" form="f-ttd" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </section>
@endsection
