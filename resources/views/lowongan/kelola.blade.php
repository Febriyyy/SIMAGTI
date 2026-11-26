@extends('layouts.app')

@section('title', 'Lowongan Magang')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Lowongan Magang</h1>
            <p class="page-sub">{{ auth()->user()->role === 'tu' ? 'Setujui ajuan supervisor sebelum terlihat mahasiswa.' : 'Diajukan ke TU Fakultas sebelum terlihat mahasiswa.' }}</p>
        </div>
        @if ($companies->isNotEmpty())
            <button type="button" class="btn btn-primary" data-open="job-new"><x-ikon name="plus" :size="18" /> Lowongan baru</button>
        @endif
    </div>

    @if ($applicants->isNotEmpty())
        <section class="card applicants-card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Pengajuan masuk <span class="dash-count">{{ $applicants->count() }}</span></h2>
                    <p class="card-sub">Lihat portofolionya sebelum memutuskan</p>
                </div>
            </div>
            <ul class="item-list">
                @foreach ($applicants as $a)
                    <li class="item-row">
                        <x-person :user="$a->student" />
                        <div class="grow min0">
                            <div class="cell-sub">{{ $a->student->studentProfile?->studyProgram?->name ?? 'Program studi belum diisi' }}, mengajukan ke {{ $a->company->name }}</div>
                        </div>
                        <a href="{{ route('portofolio.lihat', $a->student) }}" class="btn btn-sm"><x-ikon name="star" :size="16" /> Portofolio</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="card">
        @if ($postings->isEmpty())
            <div class="empty">
                <strong>Belum ada lowongan</strong>
                {{ $companies->isEmpty() ? 'Lowongan bisa dipasang setelah Anda terhubung dengan perusahaan.' : 'Pasang lowongan agar mahasiswa bisa langsung mengajukan KP ke perusahaan ini.' }}
            </div>
        @else
            <ul class="item-list">
                @php($isTu = auth()->user()->role === 'tu')
                @foreach ($postings as $p)
                    <li class="item-row job-row">
                        <x-org :name="$p->company->name" />
                        <div class="grow min0">
                            <div class="cell-title">{{ $p->title }}</div>
                            <div class="cell-sub">
                                {{ $p->company->name }}@if ($p->deadline) · tutup {{ $p->deadline->locale('id')->translatedFormat('j M Y') }}@endif @if ($p->quota) · kuota {{ $p->quota }}@endif
                                @if ($isTu && $p->status === 'pending' && $p->poster) · diajukan {{ $p->poster->listName() }}@endif
                            </div>
                            @if ($p->status === 'rejected' && $p->review_note)
                                <div class="job-review-note"><b>Catatan TU:</b> {{ $p->review_note }}</div>
                            @endif
                        </div>
                        <x-chip :tone="$p->statusTone()">{{ $p->statusLabel() }}</x-chip>
                        <div class="job-row-actions">
                            @if ($isTu && $p->status === 'pending')
                                <button type="button" class="btn btn-sm btn-ghost" data-open="job-edit-{{ $p->id }}"><x-ikon name="edit" :size="16" /> Lihat</button>
                                <button type="button" class="btn btn-sm" data-open="job-reject-{{ $p->id }}">Tolak</button>
                                <form method="POST" action="{{ route('lowongan.tinjau', $p) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" name="keputusan" value="setujui" class="btn btn-sm btn-primary"><x-ikon name="check" :size="16" /> Setujui</button>
                                </form>
                            @else
                                <button type="button" class="btn btn-sm btn-ghost" data-open="job-edit-{{ $p->id }}"><x-ikon name="edit" :size="16" /> Ubah</button>
                                <form method="POST" action="{{ route('lowongan.status', $p) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($p->status === 'published')
                                        <button type="submit" name="status" value="closed" class="btn btn-sm">Tutup</button>
                                    @elseif ($p->status !== 'pending')
                                        <button type="submit" name="status" value="published" class="btn btn-sm btn-primary">{{ $isTu ? 'Buka' : ($p->status === 'rejected' ? 'Ajukan lagi' : 'Ajukan ke TU') }}</button>
                                    @endif
                                </form>
                            @endif
                            <form method="POST" action="{{ route('lowongan.destroy', $p) }}" onsubmit="return confirm('Hapus lowongan {{ $p->title }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" aria-label="Hapus {{ $p->title }}"><x-ikon name="delete" :size="16" /></button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($companies->isNotEmpty())
        @include('lowongan._form', ['id' => 'job-new', 'action' => route('lowongan.store'), 'method' => 'POST', 'posting' => null, 'bag' => 'posting'])
    @endif
    @foreach ($postings as $p)
        @include('lowongan._form', ['id' => 'job-edit-'.$p->id, 'action' => route('lowongan.update', $p), 'method' => 'PUT', 'posting' => $p, 'bag' => 'posting'.$p->id])

        @if (auth()->user()->role === 'tu' && $p->status === 'pending')
            {{-- TU menolak lowongan dengan alasan agar supervisor bisa memperbaikinya --}}
            @php($rb = $errors->getBag('review'.$p->id))
            <dialog id="job-reject-{{ $p->id }}" class="dialog" @if ($rb->any()) data-autoopen @endif>
                <form method="POST" action="{{ route('lowongan.tinjau', $p) }}" class="dialog-body">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="keputusan" value="tolak">
                    <div class="dialog-head">
                        <div>
                            <h2 class="card-title">Tolak lowongan</h2>
                            <p class="card-sub">{{ $p->title }} · {{ $p->company->name }}</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                    </div>
                    <div class="field">
                        <label class="label" for="rej-{{ $p->id }}">Alasan</label>
                        <textarea id="rej-{{ $p->id }}" name="catatan" class="textarea" rows="3" maxlength="500" required placeholder="Lengkapi deskripsi pekerjaan dan kualifikasinya.">{{ old('catatan') }}</textarea>
                        @if ($rb->has('catatan'))<p class="field-error">{{ $rb->first('catatan') }}</p>@endif
                    </div>
                    <div class="dialog-foot row-actions">
                        <button type="button" class="btn" data-close>Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim ke supervisor</button>
                    </div>
                </form>
            </dialog>
        @endif
    @endforeach
@endsection
