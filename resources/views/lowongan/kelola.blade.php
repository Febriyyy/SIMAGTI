@extends('layouts.app')

@section('title', 'Lowongan Magang')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Lowongan Magang</h1>
            <p class="page-sub">Terlihat mahasiswa yang belum punya tempat KP.</p>
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
                @foreach ($postings as $p)
                    <li class="item-row job-row">
                        <x-org :name="$p->company->name" />
                        <div class="grow min0">
                            <div class="cell-title">{{ $p->title }}</div>
                            <div class="cell-sub">{{ $p->company->name }}@if ($p->deadline) · tutup {{ $p->deadline->locale('id')->translatedFormat('j M Y') }}@endif @if ($p->quota) · kuota {{ $p->quota }}@endif</div>
                        </div>
                        <x-chip :tone="\App\Models\JobPosting::STATUSES[$p->status][1] ?? 'gray'">{{ \App\Models\JobPosting::STATUSES[$p->status][0] ?? $p->status }}</x-chip>
                        <div class="job-row-actions">
                            <button type="button" class="btn btn-sm btn-ghost" data-open="job-edit-{{ $p->id }}"><x-ikon name="edit" :size="16" /> Ubah</button>
                            <form method="POST" action="{{ route('lowongan.status', $p) }}">
                                @csrf
                                @method('PATCH')
                                @if ($p->status === 'published')
                                    <button type="submit" name="status" value="closed" class="btn btn-sm">Tutup</button>
                                @else
                                    <button type="submit" name="status" value="published" class="btn btn-sm btn-primary">Buka</button>
                                @endif
                            </form>
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
    @endforeach
@endsection
