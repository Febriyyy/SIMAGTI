@extends('layouts.app')

@section('title', 'Silabus')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Silabus</h1>
            <p class="page-sub">Tujuan KP: hard skill, soft skill, attitude.</p>
        </div>
        @if ($internship)
            <x-chip :tone="$internship->syllabusStatusTone()">{{ $internship->syllabusStatusLabel() }}</x-chip>
        @endif
    </div>

    @if (! $internship)
        <div class="card">
            <div class="empty">
                <strong>Silabus belum bisa disusun</strong>
                Silabus disusun setelah pengajuan KP Anda disetujui. <a href="{{ route('kp.status') }}">Lihat status KP</a>.
            </div>
        </div>
    @else
        @php($reviewer = $internship->isSeminar() ? 'dosen pembimbing' : 'Company Supervisor')
        @if ($internship->syllabus_status === 'revision')
            <div class="alert alert-warning" role="status">
                <strong>Perlu revisi dari {{ $reviewer }}:</strong> {{ $internship->syllabus_note }}
            </div>
        @elseif ($internship->syllabus_status === 'submitted')
            <div class="alert alert-info" role="status">Silabus menunggu persetujuan {{ $reviewer }}. Selama menunggu, silabus tidak bisa diubah.</div>
        @elseif ($internship->syllabus_status === 'approved')
            <div class="alert alert-success" role="status">
                Disetujui {{ $reviewer }}{{ $internship->syllabus_reviewed_at ? ' pada '.$internship->syllabus_reviewed_at->locale('id')->translatedFormat('j F Y') : '' }}.
                @if ($internship->syllabus_note) Catatan: {{ $internship->syllabus_note }} @endif
            </div>
        @endif

        @include('silabus._items', ['internship' => $internship, 'canEdit' => $canEdit])

        @if ($internship->status === 'berjalan')
            <div class="form-actions">
                @if ($canEdit)
                    <form method="POST" action="{{ route('silabus.submit') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary"><x-ikon name="send" :size="18" /> Kirim ke {{ $reviewer }}</button>
                    </form>
                    <p class="hint">Isi minimal satu tujuan di setiap kelompok. Setelah dikirim, silabus terkunci sampai disetujui atau diminta revisi.</p>
                @else
                    <form method="POST" action="{{ route('silabus.reopen') }}" onsubmit="return confirm('Silabus akan perlu disetujui ulang. Lanjutkan?')">
                        @csrf
                        <button type="submit" class="btn">{{ $internship->syllabus_status === 'submitted' ? 'Tarik untuk diubah' : 'Ajukan perubahan' }}</button>
                    </form>
                @endif
            </div>
        @endif
    @endif
@endsection
