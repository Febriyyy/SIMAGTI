@extends('layouts.app')

@section('title', 'Data Sertifikat')

{{--
    Company Supervisor: logo perusahaan, nama dan tanda tangan ketua divisi,
    diisi langsung di atas sertifikat ukuran penuh (satu sertifikat per divisi).
    Variabel: companies, divisions, signatures
--}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Data Sertifikat</h1>
            <p class="page-sub">Klik logo atau tanda tangan di sertifikat.</p>
        </div>
    </div>

    @foreach ($companies as $company)
        <form id="f-logo-{{ $company->id }}" method="POST" action="{{ route('sertifikat.data.logo', $company) }}" enctype="multipart/form-data" hidden>
            @csrf
            @method('PUT')
        </form>
    @endforeach

    <?php
        // Blok kaprodi hanya dibaca: nama dan status tanda tangan, tanpa gambar tanda tangannya
        $right = [
            'role' => $campusSigner['title'],
            'name' => $campusSigner['name'],
            'owner' => 'Diisi kaprodi',
            'sub' => $campusSigner['program'],
            'status' => [$campusSigner['signed'], $campusSigner['signed'] ? 'Sudah ditandatangani kaprodi' : 'Belum ditandatangani kaprodi'],
        ];
    ?>

    @forelse ($divisions as $division)
        <?php
            $company = $division->company;
            $bag = $errors->getBag('divisi'.$division->id);
            $logoBag = $company ? $errors->getBag('logo'.$company->id) : null;
            $sig = $signatures[$division->id] ?? null;
        ?>
        <form id="f-div-{{ $division->id }}" method="POST" action="{{ route('sertifikat.data.divisi', $division) }}" enctype="multipart/form-data" hidden>
            @csrf
            @method('PUT')
        </form>

        @if ($company && ! isset($anchored[$company->id]))
            <?php $anchored[$company->id] = true; ?>
            <span id="perusahaan-{{ $company->id }}" class="cst-anchor"></span>
        @endif
        <section class="cst-studio" id="divisi-{{ $division->id }}">
            @include('sertifikat._stage', [
                'edit' => ['perusahaan' => $company ? 'f-logo-'.$company->id : null],
                'company' => $company,
                'left' => [
                    'role' => 'Ketua Divisi '.$division->name,
                    'name' => $division->head_name,
                    'sign' => $sig,
                    'sub' => $company?->name,
                    'form' => 'f-div-'.$division->id,
                    'nameField' => 'head_name',
                    'live' => 'divisi'.$division->id,
                ],
                'right' => $right,
                'shapes' => ['square', 'wide', 'sign'],
            ])

            <div class="cst-bar">
                <div class="cst-items">
                    @if ($company)
                        @include('sertifikat._bar-item', [
                            'icon' => 'image', 'title' => 'Logo perusahaan', 'sub' => $company->name,
                            'ok' => (bool) $company->logo_file_id, 'okLabel' => 'Terpasang', 'todoLabel' => 'Belum ada',
                            'messages' => [$logoBag->first('logo')],
                            'delete' => $company->logo_file_id ? route('sertifikat.data.logo.destroy', $company) : null,
                            'deleteLabel' => 'Hapus logo perusahaan', 'confirm' => 'Hapus logo '.$company->name.'?',
                        ])
                    @endif
                    @include('sertifikat._bar-item', [
                        'icon' => 'edit', 'title' => 'Ketua divisi', 'sub' => 'Divisi '.$division->name,
                        'ok' => $division->head_name && $sig, 'okLabel' => 'Lengkap', 'todoLabel' => 'Belum lengkap',
                        'messages' => [$bag->first('head_name'), $bag->first('signature')],
                        'removeForm' => $sig ? 'f-div-'.$division->id : null, 'removeName' => 'remove_signature',
                        'deleteLabel' => 'Hapus tanda tangan', 'confirm' => 'Hapus tanda tangan ketua divisi '.$division->name.'?',
                    ])
                </div>
                <button type="submit" form="f-div-{{ $division->id }}" class="btn btn-primary cst-save">Simpan</button>
            </div>
        </section>
    @empty
        @foreach ($companies as $company)
            <?php $logoBag = $errors->getBag('logo'.$company->id); ?>
            <section class="cst-studio" id="perusahaan-{{ $company->id }}">
                @include('sertifikat._stage', [
                    'edit' => ['perusahaan' => 'f-logo-'.$company->id],
                    'company' => $company,
                    'left' => ['role' => 'Ketua Divisi', 'owner' => 'Belum ada divisi', 'sub' => $company->name],
                    'right' => $right,
                    'shapes' => ['square', 'wide'],
                ])
                <div class="cst-bar">
                    <div class="cst-items">
                        @include('sertifikat._bar-item', [
                            'icon' => 'image', 'title' => 'Logo perusahaan', 'sub' => $company->name,
                            'ok' => (bool) $company->logo_file_id, 'okLabel' => 'Terpasang', 'todoLabel' => 'Belum ada',
                            'messages' => [$logoBag->first('logo')],
                            'delete' => $company->logo_file_id ? route('sertifikat.data.logo.destroy', $company) : null,
                            'deleteLabel' => 'Hapus logo perusahaan', 'confirm' => 'Hapus logo '.$company->name.'?',
                        ])
                    </div>
                    <p class="cst-note">Bisa diisi setelah ada mahasiswa di sebuah divisi.</p>
                </div>
            </section>
        @endforeach
    @endforelse
@endsection
