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
            <p class="page-sub">Klik logo perusahaan dan blok tanda tangan di kiri sertifikat. Diisi sekali, dipakai di sertifikat semua mahasiswa magang Anda.</p>
        </div>
    </div>

    @foreach ($companies as $company)
        <form id="f-logo-{{ $company->id }}" method="POST" action="{{ route('sertifikat.data.logo', $company) }}" enctype="multipart/form-data" hidden>
            @csrf
            @method('PUT')
        </form>
    @endforeach

    <?php $right = ['role' => 'Ketua Program Studi', 'owner' => 'Diisi kaprodi', 'sub' => \App\Support\Campus::get('campus.program')]; ?>

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
            ])

            <div class="cst-bar">
                @if ($company)
                    <div class="cst-item">
                        <span @class(['cst-dot', 'is-ok' => $company->logo_file_id])></span>
                        <div>
                            <strong>Logo perusahaan</strong>
                            <span>{{ $company->name }}</span>
                            @if ($logoBag->has('logo'))<p class="field-error">{{ $logoBag->first('logo') }}</p>@endif
                        </div>
                        @if ($company->logo_file_id)
                            <form method="POST" action="{{ route('sertifikat.data.logo.destroy', $company) }}" onsubmit="return confirm('Hapus logo {{ $company->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="cst-link">Hapus</button>
                            </form>
                        @endif
                    </div>
                @endif
                <div class="cst-item">
                    <span @class(['cst-dot', 'is-ok' => $division->head_name && $sig])></span>
                    <div>
                        <strong>Ketua divisi</strong>
                        <span>Divisi {{ $division->name }}</span>
                        @foreach (['head_name', 'signature'] as $f)
                            @if ($bag->has($f))<p class="field-error">{{ $bag->first($f) }}</p>@endif
                        @endforeach
                    </div>
                </div>
                <p class="cst-guide">Logo 500 × 500 px atau 900 × 300 px. Tanda tangan 600 × 200 px, PNG berlatar transparan. Maks. 1 MB, otomatis dipaskan.</p>
                <div class="cst-actions">
                    @if ($sig)
                        <label class="cst-check"><input type="checkbox" name="remove_signature" value="1" form="f-div-{{ $division->id }}"> Hapus tanda tangan</label>
                    @endif
                    <button type="submit" form="f-div-{{ $division->id }}" class="btn btn-primary">Simpan</button>
                </div>
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
                ])
                <div class="cst-bar">
                    <div class="cst-item">
                        <span @class(['cst-dot', 'is-ok' => $company->logo_file_id])></span>
                        <div>
                            <strong>Logo perusahaan</strong>
                            <span>{{ $company->name }}</span>
                            @if ($logoBag->has('logo'))<p class="field-error">{{ $logoBag->first('logo') }}</p>@endif
                        </div>
                    </div>
                    <p class="cst-guide">Ketua divisi bisa diisi setelah ada mahasiswa magang yang Anda supervisi di sebuah divisi.</p>
                </div>
            </section>
        @endforeach
    @endforelse
@endsection
