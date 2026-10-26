@extends('layouts.app')

@section('title', 'Timesheet')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Timesheet</h1>
            <p class="page-sub">Rencana kegiatan dulu, lalu buat task di bawahnya.</p>
        </div>
        @if ($internship)
            <div class="row-actions">
                <details class="menu">
                    <summary class="btn"><x-ikon name="download" :size="18" /> Unduh</summary>
                    <div class="menu-panel">
                        <a href="{{ route('kegiatan.unduh', ['format' => 'xlsx', 'periode' => $all ? 'semua' : 'rentang', 'dari' => $period->start->toDateString(), 'sampai' => $period->end->toDateString()]) }}" class="menu-item"><x-ikon name="excel" :size="16" /> Excel (.xlsx)</a>
                        <a href="{{ route('kegiatan.unduh', ['format' => 'pdf', 'periode' => $all ? 'semua' : 'rentang', 'dari' => $period->start->toDateString(), 'sampai' => $period->end->toDateString()]) }}" target="_blank" rel="noopener" class="menu-item"><x-ikon name="print" :size="16" /> PDF (cetak)</a>
                        <p class="menu-note">{{ $all ? 'Seluruh periode KP.' : $period->label }}</p>
                    </div>
                </details>
                @if ($canEdit)
                    <button type="button" class="btn btn-primary" data-open="dlg-plan-new"><x-ikon name="plus" :size="18" /> Rencana baru</button>
                @endif
            </div>
        @endif
    </div>

    @if (! $internship)
        <div class="card">
            <div class="empty">
                <strong>Belum bisa dipakai</strong>
                Timesheet aktif setelah pengajuan KP Anda disetujui. <a href="{{ route('kp.status') }}">Lihat status KP</a>.
            </div>
        </div>
    @else
        @php($active = $plans->filter(fn ($p) => $p->phase() === 'active')->count())
        <div class="stat-grid">
            <x-stat label="Rencana kegiatan" :value="$plans->count()" unit="kegiatan" :hint="$active > 0 ? $active.' sedang berjalan' : 'Tidak ada yang berjalan hari ini'" />
            <x-stat label="Task selesai" :value="$plans->sum('completed_tasks_count')" :unit="'/ '.$plans->sum('tasks_count')" tone="green"
                    :percent="$plans->sum('tasks_count') > 0 ? (int) round($plans->sum('completed_tasks_count') / $plans->sum('tasks_count') * 100) : 0" />
            <x-stat label="Hari tercatat" :value="$days->count()" unit="hari" tone="gray" :hint="$all ? 'Seluruh periode' : $period->short" />
        </div>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Rencana kegiatan</h2>
                    <p class="card-sub">{{ $internship->periodLabel() }} · {{ $internship->totalWeeks() }} minggu</p>
                </div>
            </div>
            @if ($plans->isEmpty())
                <div class="empty">
                    <strong>Belum ada rencana</strong>
                    Susun kegiatan yang akan dikerjakan per minggu atau per bulan. Task baru bisa dibuat setelah ada rencana.
                    @if ($canEdit)
                        <div class="row-actions" style="justify-content: center; margin-top: var(--space-3)">
                            <button type="button" class="btn btn-primary" data-open="dlg-plan-new"><x-ikon name="plus" :size="18" /> Rencana baru</button>
                        </div>
                    @endif
                </div>
            @else
                @include('timesheet._gantt', ['canEdit' => $canEdit])
            @endif
        </section>

        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div class="period-nav">
                            <a href="{{ route('timesheet.index', ['p' => $offset - 1]) }}" class="btn btn-sm btn-icon" aria-label="Periode sebelumnya"><x-ikon name="back" :size="18" /></a>
                            <div>
                                <h2 class="card-title">Catatan kegiatan</h2>
                                <p class="card-sub">{{ $all ? 'Seluruh periode KP' : $period->label }} · {{ $entries->count() }} catatan</p>
                            </div>
                            @if ($offset < 0)
                                <a href="{{ route('timesheet.index', ['p' => $offset + 1]) }}" class="btn btn-sm btn-icon" aria-label="Periode berikutnya"><x-ikon name="arrow" :size="18" /></a>
                            @endif
                        </div>
                        <div class="segmented">
                            <a href="{{ route('timesheet.index') }}" @class(['seg', 'is-active' => ! $all && $offset === 0])>Periode ini</a>
                            <a href="{{ route('timesheet.index', ['periode' => 'semua']) }}" @class(['seg', 'is-active' => $all])>Semua</a>
                        </div>
                    </div>

                    @if ($days->isEmpty())
                        <div class="empty">
                            <strong>Belum ada catatan</strong>
                            Catat kegiatan harian Anda di formulir samping.
                        </div>
                    @else
                        <div class="days">
                            @foreach ($days as $date => $items)
                                @php($day = \Illuminate\Support\Carbon::parse($date)->locale('id'))
                                <div class="day">
                                    <div class="day-head">
                                        <span class="day-date"><strong>{{ $day->translatedFormat('l') }}</strong>, {{ $day->translatedFormat('j F Y') }}</span>
                                        <span class="day-total">{{ $items->count() }} catatan</span>
                                    </div>
                                    <ul class="time-list">
                                        @foreach ($items as $entry)
                                            <li>
                                                <span class="time-dot" aria-hidden="true"></span>
                                                <div class="grow min0">
                                                    <div class="time-desc">{{ $entry->description }}</div>
                                                    @if ($entry->task)
                                                        <a href="{{ route('tugas.show', $entry->task) }}" class="cell-sub">{{ $entry->task->key() }} · {{ $entry->task->title }}</a>
                                                    @endif
                                                </div>
                                                @if ($entry->photo)
                                                    <a href="{{ route('timesheet.foto', $entry) }}" target="_blank" rel="noopener" class="time-photo" title="Foto dokumentasi">
                                                        <img src="{{ route('timesheet.foto', $entry) }}" alt="Foto dokumentasi {{ $day->translatedFormat('j M') }}" loading="lazy" width="44" height="44">
                                                    </a>
                                                @endif
                                                @if ($canEdit)
                                                    <form method="POST" action="{{ route('timesheet.destroy', $entry) }}" onsubmit="return confirm('Hapus catatan ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-icon btn-ghost" aria-label="Hapus catatan"><x-ikon name="delete" :size="16" /></button>
                                                    </form>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <div class="col">
                @if ($canEdit)
                    <section class="card">
                        <div class="card-head">
                            <h2 class="card-title">Catat kegiatan</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('timesheet.store') }}" enctype="multipart/form-data" class="stack">
                                @csrf
                                <div>
                                    <label class="label" for="ts-date">Tanggal</label>
                                    <input id="ts-date" type="date" name="work_date" class="input" value="{{ old('work_date', now()->min($internship->end_date)->toDateString()) }}" required>
                                </div>
                                <div>
                                    <label class="label" for="ts-task">Task (opsional)</label>
                                    <select id="ts-task" name="weekly_task_id" class="select">
                                        <option value="">Tidak terkait task</option>
                                        @foreach ($tasks as $task)
                                            <option value="{{ $task->id }}" @selected((int) old('weekly_task_id') === $task->id)>{{ $task->key() }} · {{ \Illuminate\Support\Str::limit($task->title, 50) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label" for="ts-desc">Aktivitas pekerjaan</label>
                                    <textarea id="ts-desc" name="description" class="textarea" rows="3" maxlength="1000" required placeholder="Apa yang dikerjakan hari ini?">{{ old('description') }}</textarea>
                                </div>
                                <div>
                                    <label class="label" for="ts-photo">Foto dokumentasi (opsional)</label>
                                    <input id="ts-photo" type="file" name="foto" class="input" accept=".jpg,.jpeg,.png,.webp">
                                    <p class="hint">JPG, PNG, atau WEBP, maksimal 5 MB.</p>
                                </div>
                                <div class="row-actions"><button type="submit" class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </section>
                @endif
            </div>
        </div>

        @if ($canEdit)
            @php($failedPlan = $errors->any() ? old('_plan') : null)
            <dialog id="dlg-plan-new" class="dialog" @if ($failedPlan === 'new') data-autoopen @endif>
                <form method="POST" action="{{ route('rencana.store') }}" class="dialog-body">
                    @csrf
                    <input type="hidden" name="_plan" value="new">
                    <div class="dialog-head">
                        <h2 class="card-title">Rencana baru</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                    </div>
                    @include('timesheet._plan-form', ['plan' => null, 'useOld' => $failedPlan === 'new', 'colorIndex' => count($plans)])
                    <div class="dialog-foot row-actions">
                        <button type="button" class="btn" data-close>Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </dialog>

            @foreach ($plans as $plan)
                <dialog id="dlg-plan-{{ $plan->id }}" class="dialog" @if ((string) $failedPlan === (string) $plan->id) data-autoopen @endif>
                    <div class="dialog-body">
                        <form method="POST" action="{{ route('rencana.update', $plan) }}" id="plan-form-{{ $plan->id }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_plan" value="{{ $plan->id }}">
                            <div class="dialog-head">
                                <h2 class="card-title">Ubah rencana</h2>
                                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                            </div>
                            @include('timesheet._plan-form', ['plan' => $plan, 'useOld' => (string) $failedPlan === (string) $plan->id, 'colorIndex' => $loop->index])
                        </form>
                        <div class="dialog-foot row-actions">
                            @if ($plan->tasks_count === 0)
                                <form method="POST" action="{{ route('rencana.destroy', $plan) }}" onsubmit="return confirm('Hapus rencana {{ $plan->title }}?')" class="push-left">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost-danger">Hapus</button>
                                </form>
                            @endif
                            <button type="button" class="btn" data-close>Batal</button>
                            <button type="submit" class="btn btn-primary" form="plan-form-{{ $plan->id }}">Simpan</button>
                        </div>
                    </div>
                </dialog>
            @endforeach
        @endif
    @endif
@endsection
