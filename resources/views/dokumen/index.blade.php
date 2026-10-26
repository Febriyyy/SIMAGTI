@extends('layouts.app')

@section('title', 'Dokumen KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Dokumen KP</h1>
            <p class="page-sub">Surat balasan boleh menyusul sampai batas waktu.</p>
        </div>
    </div>

    @if (! $internship)
        <div class="card"><div class="empty"><strong>Belum ada pengajuan KP</strong> Dokumen muncul setelah Anda mengajukan KP. <a href="{{ route('kp.status') }}">Buka Status KP</a>.</div></div>
    @else
        @php
            $letter = $internship->document(\App\Models\InternshipDocument::LETTER);
            $uploads = collect([\App\Models\InternshipDocument::REPLY])
                ->map(fn ($t) => $internship->document($t))->filter();
        @endphp

        <section class="card doc">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Surat pengantar</h2>
                    <p class="card-sub">Untuk {{ $internship->company?->name ?? 'perusahaan tujuan' }}</p>
                </div>
                @if ($letter)<x-chip :tone="$letter->statusTone()">{{ $letter->statusLabel() }}</x-chip>@endif
            </div>
            <div class="card-body">
                @if (! $letter)
                    <p>Minta surat pengantar bila perusahaan memerlukannya. TU Fakultas menerbitkannya dengan nomor surat otomatis, lalu Anda bisa mencetaknya dari sini.</p>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('dokumen.minta') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary"><x-ikon name="send" :size="18" /> Minta surat pengantar</button>
                        </form>
                    @endif
                @elseif ($letter->status === 'diminta')
                    <p>Diminta {{ $letter->created_at->locale('id')->translatedFormat('j F Y') }}. TU Fakultas akan menerbitkannya. <x-waiting :since="$letter->created_at" /></p>
                @else
                    <p>Nomor <strong>{{ $letter->number }}</strong>, terbit {{ $letter->processed_at?->locale('id')->translatedFormat('j F Y') }}.</p>
                    <a href="{{ route('dokumen.cetak', $letter) }}" target="_blank" rel="noopener" class="btn"><x-ikon name="print" :size="18" /> Cetak / simpan PDF</a>
                @endif
            </div>
        </section>

        @forelse ($uploads as $doc)
            <section class="card doc">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">{{ $doc->label() }}</h2>
                        <p class="card-sub">{{ \App\Models\InternshipDocument::TYPES[$doc->type][1] ?? '' }}</p>
                    </div>
                    <x-chip :tone="$doc->isOverdue() ? 'red' : $doc->statusTone()">{{ $doc->isOverdue() ? 'Lewat batas' : $doc->statusLabel() }}</x-chip>
                </div>
                <div class="card-body stack">
                    @if ($doc->status === 'ditolak' && $doc->note)
                        <div class="alert alert-warning" role="status"><strong>Perlu diunggah ulang:</strong> {{ $doc->note }}</div>
                    @endif
                    @if ($doc->due_on && ! $doc->isComplete())
                        <p class="muted">Batas unggah {{ $doc->due_on->locale('id')->translatedFormat('j F Y') }}. Kegiatan KP tetap bisa berjalan selama menunggu.</p>
                    @endif
                    @if ($doc->file)
                        <p><a href="{{ route('dokumen.berkas', $doc) }}" target="_blank" rel="noopener"><x-ikon name="paperclip" :size="16" /> {{ $doc->file->original_name }}</a></p>
                    @endif
                    @if ($canEdit && in_array($doc->status, ['menunggu', 'ditolak', 'diunggah'], true))
                        <form method="POST" action="{{ route('dokumen.unggah', $doc->type) }}" enctype="multipart/form-data" class="inline-form">
                            @csrf
                            <input type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png" required class="input" aria-label="Berkas {{ $doc->label() }}">
                            <button type="submit" class="btn btn-primary"><x-ikon name="upload" :size="18" /> {{ $doc->file ? 'Ganti berkas' : 'Unggah' }}</button>
                        </form>
                        <p class="hint">PDF, JPG, atau PNG, maksimal 5 MB. Diperiksa TU Fakultas.</p>
                    @endif
                </div>
            </section>
        @empty
            <div class="card"><div class="empty"><strong>Surat balasan belum perlu</strong> Kolom unggah muncul setelah pengajuan KP disetujui.</div></div>
        @endforelse
    @endif
@endsection
