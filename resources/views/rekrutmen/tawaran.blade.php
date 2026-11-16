@extends('layouts.app')

@section('title', 'Tawaran Kerja')

@section('content')
<?php
    $o = $offer;
    $left = $o->daysLeft();
    $open = $o->isOpen();
    $points = $o->jobPoints();
?>
    <div class="page-head">
        <div>
            <h1 class="page-title">Tawaran Kerja</h1>
            <p class="page-sub">Baca detail dan surat resminya sebelum memutuskan.</p>
        </div>
    </div>

    {{-- Kepala tawaran: perusahaan, posisi, dan batas waktu --}}
    <section class="card job-head">
        <x-org :name="$internship->placeName()" class="job-org" />
        <div class="min0 grow">
            <p class="job-company">{{ $internship->placeName() }}</p>
            <h2 class="job-title">{{ $o->offered_position }}</h2>
            <p class="job-meta">
                @if ($o->unit)<span>{{ $o->unit }}</span>@endif
                <span>{{ $o->work_location }}</span>
                <span>{{ $o->workModeLabel() }}</span>
            </p>
        </div>
        <div class="job-state">
            @if ($o->status === 'ditawari' && $open)
                <span class="job-due {{ $left <= 2 ? 'is-soon' : '' }}">{{ $left === 0 ? 'Hari ini terakhir' : $left.' hari lagi' }}</span>
                <small>Jawab sebelum {{ $o->respond_by?->locale('id')->translatedFormat('j F Y') }}</small>
            @else
                <x-chip :tone="$o->statusTone()">{{ $o->status === 'ditolak' ? 'Anda menolak' : ($o->status === 'diterima' ? 'Anda menerima' : $o->statusLabel()) }}</x-chip>
                @if ($o->responded_at)<small>{{ $o->responded_at->locale('id')->translatedFormat('j F Y') }}</small>@endif
            @endif
        </div>
    </section>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Detail pekerjaan</h2></div>
                <div class="card-body">
                    <dl class="job-facts">
                        <div><dt>Hubungan kerja</dt><dd>{{ $o->employmentLabel() }}</dd></div>
                        <div><dt>Perkiraan mulai</dt><dd>{{ $o->start_date?->locale('id')->translatedFormat('j F Y') ?? 'Disepakati kemudian' }}</dd></div>
                        <div><dt>Penempatan</dt><dd>{{ $o->work_location }}</dd></div>
                        <div><dt>Cara kerja</dt><dd>{{ $o->workModeLabel() }}</dd></div>
                        <div class="span-all"><dt>Kompensasi</dt><dd>{{ $o->compensation ?: 'Dibahas saat wawancara' }}</dd></div>
                    </dl>
                    @if ($points)
                        <h3 class="job-sub">Tanggung jawab</h3>
                        <ul class="job-points">
                            @foreach ($points as $point)<li>{{ $point }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            </section>

            @if ($o->offerLetter)
                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Surat resmi perusahaan</h2>
                        <a href="{{ route('rekrutmen.surat', $o) }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost"><x-ikon name="download" :size="16" /> Buka</a>
                    </div>
                    <iframe class="job-letter" src="{{ route('rekrutmen.surat', $o) }}#view=FitH" title="Surat penawaran kerja dari {{ $internship->placeName() }}"></iframe>
                </section>
            @endif
        </div>

        <div class="col">
            <section class="card job-decide">
                <div class="card-head"><h2 class="card-title">Keputusan Anda</h2></div>
                <div class="card-body stack">
                    @if ($o->status === 'ditawari' && $open)
                        <p class="muted">Setelah dikirim, jawaban tidak bisa diubah dari aplikasi.</p>
                        <button type="button" class="btn btn-primary btn-block" data-open="dlg-accept"><x-ikon name="check" :size="16" /> Terima tawaran</button>
                        <button type="button" class="btn btn-block" data-open="dlg-decline">Tolak tawaran</button>
                    @elseif ($o->status === 'diterima')
                        <p><strong>Selamat!</strong> Perusahaan sudah menerima jawaban Anda. Langkah selanjutnya akan disampaikan oleh kontak HR di bawah.</p>
                    @elseif ($o->status === 'ditolak')
                        <p>Anda menolak tawaran ini: <strong>{{ mb_strtolower((string) $o->declineReasonLabel()) }}</strong>.</p>
                        @if ($o->decline_note)<p class="muted">“{{ $o->decline_note }}”</p>@endif
                    @else
                        <p>Batas waktu menjawab sudah lewat. Hubungi kontak HR bila Anda masih berminat; perusahaan bisa memperpanjang batasnya.</p>
                    @endif
                </div>
                <div class="card-foot job-contact">
                    <span class="job-contact-label">Kontak HR</span>
                    <strong>{{ $o->hr_contact_name }}</strong>
                    <a href="mailto:{{ $o->hr_contact_email }}">{{ $o->hr_contact_email }}</a>
                </div>
            </section>
        </div>
    </div>

    @if ($o->status === 'ditawari' && $open)
        <dialog id="dlg-accept" class="dialog">
            <form method="POST" action="{{ route('rekrutmen.jawab') }}" class="dialog-body">
                @csrf
                @method('PATCH')
                <input type="hidden" name="jawaban" value="terima">
                <div class="dialog-head">
                    <h2 class="card-title">Terima tawaran kerja?</h2>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <p>Anda menerima posisi <strong>{{ $o->offered_position }}</strong> di {{ $internship->placeName() }}. Perusahaan dan dosen pembimbing akan diberi tahu.</p>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Terima tawaran</button>
                </div>
            </form>
        </dialog>

        <dialog id="dlg-decline" class="dialog" @if ($errors->hasAny(['alasan', 'pesan'])) data-autoopen @endif>
            <form method="POST" action="{{ route('rekrutmen.jawab') }}" class="dialog-body">
                @csrf
                @method('PATCH')
                <input type="hidden" name="jawaban" value="tolak">
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Tolak tawaran</h2>
                        <p class="card-sub">Alasan Anda dikirim ke perusahaan</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <fieldset class="decline-reasons">
                    <legend class="label">Alasan</legend>
                    @foreach (\App\Models\ConversionCandidate::DECLINE_REASONS as $k => $label)
                        <label class="decline-opt">
                            <input type="radio" name="alasan" value="{{ $k }}" @checked(old('alasan') === $k) required>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    @error('alasan')<p class="field-error">{{ $message }}</p>@enderror
                </fieldset>
                <div>
                    <label class="label" for="dc-note">Pesan untuk perusahaan <span class="muted">(wajib bila memilih alasan lain)</span></label>
                    <textarea id="dc-note" name="pesan" class="textarea" rows="3" maxlength="500">{{ old('pesan') }}</textarea>
                    @error('pesan')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak tawaran</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
