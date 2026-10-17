@extends('layouts.app')

@section('title', $task->title)

@section('content')
@php
    $backUrl = $isMentor
        ? route('mentor.tugas', ['magang' => $internship->id, 'minggu' => $task->week_number])
        : route('tugas.index', ['minggu' => $task->week_number]);
@endphp
    <div class="page-head">
        <div>
            <a href="{{ $backUrl }}" class="back"><x-icon name="back" :size="16" /> Kembali ke task minggu ke-{{ $task->week_number }}</a>
            <h1 class="page-title">{{ $task->title }}</h1>
            <p class="page-sub">{{ $task->dayLabel() }} · Minggu ke-{{ $task->week_number }}</p>
        </div>
        <x-chip :tone="$task->statusTone()">{{ $task->statusLabel() }}</x-chip>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Deskripsi</h2>
                </div>
                <div class="card-body">
                    @if ($task->description)
                        <p>{{ $task->description }}</p>
                    @else
                        <p class="muted">Tidak ada deskripsi.</p>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Bukti pengerjaan</h2>
                        <p class="card-sub">Link GitHub, Google Drive, atau dokumen lain.</p>
                    </div>
                </div>
                <div class="card-body">
                    @if ($isStudent && ! $locked)
                        <form method="POST" action="{{ route('tugas.bukti', $task) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="url" name="evidence_url" class="input" placeholder="https://..." value="{{ old('evidence_url') }}" required aria-label="Link bukti pengerjaan">
                            <button type="submit" class="btn btn-primary">Tambah</button>
                        </form>
                    @endif

                    @forelse ($task->evidences as $evidence)
                        <a href="{{ $evidence->url }}" target="_blank" rel="noopener noreferrer" class="link-inline" style="margin-top: 12px">
                            <x-icon name="link" :size="16" /> {{ $evidence->title ?: 'Buka bukti' }}
                            <span class="muted">· {{ $evidence->created_at->locale('id')->translatedFormat('j M, H:i') }}</span>
                        </a>
                    @empty
                        <p class="muted">{{ $isMentor ? 'Mahasiswa belum mengirim bukti.' : 'Belum ada bukti yang dikirim.' }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">{{ $isMentor ? 'Tinjauan mentor' : 'Komentar mentor' }}</h2>
                </div>
                <div class="card-body">
                    @forelse ($task->comments as $comment)
                        <div class="comment">
                            <div class="comment-meta"><strong>{{ $comment->author?->name ?? 'Pengguna' }}</strong> · {{ $comment->created_at->locale('id')->translatedFormat('j M, H:i') }}</div>
                            <p>{{ $comment->body }}</p>
                        </div>
                    @empty
                        <p class="muted">Belum ada komentar dari mentor.</p>
                    @endforelse

                    @if ($isMentor && ! $locked)
                        <form method="POST" action="{{ route('mentor.tugas.update', $task) }}" class="stack" style="margin-top: 16px">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="status" class="label">Status</label>
                                <select id="status" name="status" class="select">
                                    @foreach (\App\Models\WeeklyTask::STATUSES as $key => [$label, $tone])
                                        <option value="{{ $key }}" @selected(old('status', $task->status) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="comment" class="label">Tambah komentar</label>
                                <textarea id="comment" name="comment" class="textarea" maxlength="500" placeholder="Tulis komentar untuk mahasiswa">{{ old('comment') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    @elseif ($locked)
                        <p class="muted" style="margin-top: 12px">KP sudah selesai, data hanya bisa dibaca.</p>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Informasi</h2>
                </div>
                <div class="card-body">
                    <dl class="kv">
                        <div>
                            <dt>Mahasiswa</dt>
                            <dd>{{ $internship->student->name }}<small>{{ $internship->student->nim }}</small></dd>
                        </div>
                        <div><dt>Perusahaan</dt><dd>{{ $internship->company->name }}</dd></div>
                        <div><dt>Mentor</dt><dd>{{ $internship->mentor?->name ?? '–' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
@endsection
