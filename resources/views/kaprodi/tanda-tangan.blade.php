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

    <?php $complete = $divisions->where('complete', true)->count(); ?>
    <section class="cst-studio">
        @include('sertifikat._stage', [
            'edit' => [],
            'company' => null,
            'left' => [
                'role' => 'Ketua Divisi', 'owner' => 'Diisi perusahaan', 'sub' => 'Perusahaan',
                'status' => $divisions->isEmpty() ? null : [$complete === $divisions->count(), $complete.' dari '.$divisions->count().' divisi lengkap'],
            ],
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

    {{-- Kelengkapan dari Company Supervisor: hanya status, tanpa gambar tanda tangan --}}
    <section class="card cpl" aria-labelledby="cpl-title">
        <div class="card-head">
            <div>
                <h2 class="card-title" id="cpl-title">Kelengkapan dari perusahaan</h2>
                <p class="card-sub">Logo perusahaan, nama, dan tanda tangan ketua divisi diisi Company Supervisor lewat menu Data Sertifikat. Bagian yang belum diisi kosong di sertifikat.</p>
            </div>
            @if ($divisions->isNotEmpty())
                <span @class(['cpl-count', 'is-ok' => $complete === $divisions->count()])>{{ $complete }}/{{ $divisions->count() }} lengkap</span>
            @endif
        </div>

        @if ($divisions->isEmpty())
            <div class="empty"><strong>Belum ada divisi</strong> Daftar muncul setelah ada mahasiswa yang magang di sebuah divisi.</div>
        @else
            <ul class="cpl-list">
                @foreach ($divisions as $row)
                    @php($division = $row['division'])
                    <li @class(['cpl-row', 'is-complete' => $row['complete']])>
                        <div class="cpl-who">
                            <strong>{{ $division->company?->name ?? 'Perusahaan' }}</strong>
                            <span>Divisi {{ $division->name }}{{ $division->head_name ? ', '.$division->head_name : '' }} · {{ $division->students_count }} mahasiswa</span>
                        </div>
                        <ul class="cpl-checks" aria-label="Kelengkapan">
                            @foreach (['logo' => 'Logo', 'name' => 'Nama ketua', 'signature' => 'Tanda tangan'] as $key => $label)
                                <li @class(['cpl-check', 'is-ok' => $row['checks'][$key]])>
                                    <x-ikon :name="$row['checks'][$key] ? 'tick' : 'close'" :size="14" />
                                    {{ $label }}<span class="sr-only">: {{ $row['checks'][$key] ? 'sudah' : 'belum' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
