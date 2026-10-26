@extends('layouts.app')

@section('title', $task->key().' '.$task->title)

@section('content')
    <div class="page-head">
        <div class="min0">
            <a href="{{ $backUrl }}" class="back"><x-ikon name="back" :size="16" /> {{ $isStudent ? 'Kembali ke kegiatan' : 'Kembali ke kegiatan '.$internship->student->firstName() }}</a>
            <h1 class="page-title"><span class="title-key">{{ $task->key() }}</span> {{ $task->title }}</h1>
            <p class="page-sub">
                {{ $task->project?->name ?? 'Tanpa proyek' }}
                · dibuat {{ $task->created_at->locale('id')->translatedFormat('j M Y') }}
                @if ($task->completed_at) · selesai {{ $task->completed_at->locale('id')->translatedFormat('j M Y') }} @endif
            </p>
        </div>
        <x-chip :tone="$task->column?->tone() ?? $task->statusTone()">{{ $task->column?->name ?? $task->statusLabel() }}</x-chip>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Deskripsi</h2>
                    @if ($canEdit)
                        <button type="button" class="btn btn-sm" data-open="dlg-edit"><x-ikon name="edit" :size="16" /> Ubah</button>
                    @endif
                </div>
                <div class="card-body">
                    @if ($task->description)
                        <p class="prewrap">{{ $task->description }}</p>
                    @else
                        <p class="muted">Tidak ada deskripsi.</p>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Checklist</h2>
                        @if ($task->checklist->isNotEmpty())
                            <p class="card-sub">{{ $task->checklist->where('is_done', true)->count() }} dari {{ $task->checklist->count() }} selesai</p>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if ($task->checklist->isNotEmpty())
                        @php($pct = (int) round($task->checklist->where('is_done', true)->count() / $task->checklist->count() * 100))
                        <div class="progress" style="margin-bottom: var(--space-3)"><span style="width: {{ $pct }}%"></span></div>
                    @endif
                    <ul class="checklist">
                        @forelse ($task->checklist as $item)
                            <li @class(['is-done' => $item->is_done])>
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('checklist.update', [$task, $item]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_done" value="{{ $item->is_done ? 0 : 1 }}">
                                        <button type="submit" class="check-btn" aria-label="{{ $item->is_done ? 'Tandai belum' : 'Tandai selesai' }}: {{ $item->text }}">
                                            <x-ikon name="check" :size="18" :fill="$item->is_done" />
                                        </button>
                                    </form>
                                @else
                                    <span class="check-btn" aria-hidden="true"><x-ikon name="check" :size="18" :fill="$item->is_done" /></span>
                                @endif
                                <span class="grow">{{ $item->text }}</span>
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('checklist.destroy', [$task, $item]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-ghost" aria-label="Hapus {{ $item->text }}">×</button>
                                    </form>
                                @endif
                            </li>
                        @empty
                            <li class="muted">Belum ada checklist.</li>
                        @endforelse
                    </ul>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('checklist.store', $task) }}" class="inline-form" style="margin-top: var(--space-2)">
                            @csrf
                            <input type="text" name="text" class="input input-sm" maxlength="200" required placeholder="Tambah butir checklist" aria-label="Butir checklist baru">
                            <button type="submit" class="btn btn-sm">Tambah</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Bukti pengerjaan</h2>
                        <p class="card-sub">Link GitHub/Drive, atau unggah foto, PDF, ZIP, dan berkas lain.</p>
                    </div>
                </div>
                <div class="card-body">
                    @include('tugas._evidences')
                </div>
            </section>

            @if ($task->attachments->isNotEmpty())
                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Lampiran lama</h2>
                    </div>
                    <div class="card-body">
                        @include('tugas._attachments', ['canManage' => false])
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Diskusi dan catatan revisi</h2>
                        <p class="card-sub">Mahasiswa, Company Supervisor, dan dosen pembimbing</p>
                    </div>
                    @if ($task->comments->isNotEmpty())
                        <span class="count-badge">{{ $task->comments->count() }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <ul class="thread">
                        @forelse ($task->comments as $comment)
                            <li class="thread-item">
                                @if ($comment->author)
                                    <x-avatar :user="$comment->author" small />
                                @endif
                                <div class="thread-body">
                                    <div class="comment-meta">
                                        <strong>{{ $comment->author?->name ?? 'Pengguna' }}</strong>
                                        <span class="muted">· {{ $comment->author?->roleLabelFor(auth()->user()) }} · {{ $comment->created_at->locale('id')->translatedFormat('j M, H:i') }}</span>
                                    </div>
                                    <p class="prewrap">{{ $comment->body }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="muted">Belum ada diskusi.</li>
                        @endforelse
                    </ul>
                    @if ($canComment)
                        <form method="POST" action="{{ route('tugas.komentar', $task) }}" class="stack" style="margin-top: var(--space-4)">
                            @csrf
                            <textarea name="body" class="textarea" rows="3" maxlength="2000" required placeholder="{{ $isStudent ? 'Tulis pertanyaan atau pembaruan…' : 'Tulis catatan atau revisi untuk mahasiswa…' }}" aria-label="Komentar"></textarea>
                            <div class="row-actions"><button type="submit" class="btn btn-sm btn-primary"><x-ikon name="send" :size="16" /> Kirim</button></div>
                        </form>
                    @elseif ($locked)
                        <p class="muted" style="margin-top: var(--space-3)">KP sudah selesai, data hanya bisa dibaca.</p>
                    @endif
                </div>
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Detail</h2>
                </div>
                <div class="card-body">
                    <dl class="kv">
                        <div><dt>Status</dt><dd><x-chip :tone="$task->column?->tone() ?? $task->statusTone()">{{ $task->column?->name ?? $task->statusLabel() }}</x-chip></dd></div>
                        <div><dt>Prioritas</dt><dd><span class="prio prio-{{ $task->priority }}"><x-ikon name="flag" :size="14" :fill="in_array($task->priority, ['urgent', 'high'], true)" /> {{ $task->priorityLabel() }}</span></dd></div>
                        <div><dt>Mulai</dt><dd>{{ $task->start_date?->locale('id')->translatedFormat('j M Y') ?? '–' }}</dd></div>
                        <div><dt>Tenggat</dt><dd @class(['is-late' => $task->isOverdue()])>{{ $task->due_date?->locale('id')->translatedFormat('j M Y') ?? '–' }}</dd></div>
                        <div><dt>Rencana</dt><dd>{{ $task->plan?->title ?? '–' }}@if ($task->plan)<small>{{ $task->plan->rangeLabel() }}</small>@endif</dd></div>
                        <div><dt>Goal</dt><dd>{{ $task->goal?->title ?? '–' }}</dd></div>
                        @if ($task->labels)
                            <div><dt>Label</dt><dd>@foreach ($task->labels as $label)<span class="tag tag-gray">{{ $label }}</span> @endforeach</dd></div>
                        @endif
                    </dl>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('kegiatan.tugas.update', $task) }}" class="inline-form" style="margin-top: var(--space-4)">
                            @csrf
                            @method('PATCH')
                            <select name="board_column_id" class="select select-sm" aria-label="Pindah ke kolom">
                                @foreach ($columns as $column)
                                    <option value="{{ $column->id }}" @selected($column->id === $task->board_column_id)>{{ $column->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm">Pindahkan</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Catatan kegiatan</h2>
                        <p class="card-sub">Masuk ke timesheet</p>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="time-list">
                        @forelse ($task->timeEntries->sortByDesc('work_date') as $entry)
                            <li>
                                <span class="time-dot" aria-hidden="true"></span>
                                <div class="grow min0">
                                    <div class="cell-sub">{{ $entry->work_date->locale('id')->translatedFormat('D, j M Y') }}</div>
                                    <div class="time-desc">{{ $entry->description }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="muted">Belum ada catatan kegiatan.</li>
                        @endforelse
                    </ul>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('timesheet.store') }}" enctype="multipart/form-data" class="stack time-form">
                            @csrf
                            <input type="hidden" name="weekly_task_id" value="{{ $task->id }}">
                            <div>
                                <label class="label" for="tl-date">Tanggal</label>
                                <input id="tl-date" type="date" name="work_date" class="input" value="{{ now()->min($internship->end_date)->toDateString() }}" required>
                            </div>
                            <input type="text" name="description" class="input" maxlength="1000" required value="{{ $task->title }}" aria-label="Kegiatan">
                            <div class="row-actions"><button type="submit" class="btn btn-sm btn-primary">Catat</button></div>
                        </form>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Mahasiswa</h2>
                </div>
                <div class="card-body">
                    <dl class="kv">
                        <div><dt>Nama</dt><dd>{{ $internship->student->name }}<small>{{ $internship->student->nim }}</small></dd></div>
                        <div><dt>Tempat KP</dt><dd>{{ $internship->placeName() }}@if ($internship->placeDetail())<small>{{ $internship->placeDetail() }}</small>@endif</dd></div>
                        @if ($internship->hasCompany())
                            <div><dt>Company Supervisor</dt><dd>{{ $internship->supervisor?->listName() ?? '–' }}</dd></div>
                        @endif
                        <div><dt>{{ $internship->lecturer?->roleLabelFor(auth()->user()) ?? 'Dosen pembimbing' }}</dt><dd>{{ $internship->lecturer?->listName() ?? '–' }}</dd></div>
                    </dl>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('kegiatan.tugas.destroy', $task) }}" onsubmit="return confirm('Hapus task {{ $task->key() }}?')" style="margin-top: var(--space-4)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost-danger"><x-ikon name="delete" :size="16" /> Hapus task</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>

    @if ($canEdit)
        <dialog id="dlg-edit" class="dialog">
            <form method="POST" action="{{ route('kegiatan.tugas.update', $task) }}" class="dialog-body">
                @csrf
                @method('PATCH')
                <div class="dialog-head">
                    <h2 class="card-title">Ubah task {{ $task->key() }}</h2>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                @include('kegiatan._task-form', ['task' => $task])
                <div class="dialog-foot">
                    <span></span>
                    <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
