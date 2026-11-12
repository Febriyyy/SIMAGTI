@extends('layouts.app')

@section('title', 'Timesheet')

@section('content')
    @php($active = $internship ? $plans->filter(fn ($p) => $p->phase() === 'active')->count() : 0)
    @php($tasksTotal = $internship ? $plans->sum('tasks_count') : 0)
    @php($tasksDone = $internship ? $plans->sum('completed_tasks_count') : 0)
    {{-- Banner: judul, pil ringkas (pengganti strip angka), tombol --}}
    <section class="pbanner">
        <div class="pbanner-text">
            <h1 class="page-title">Timesheet</h1>
            @if ($internship)
                <div class="pbanner-pills">
                    <span class="pill"><x-ikon name="calendar" :size="14" /> {{ $internship->periodLabel() }}</span>
                    <span class="pill pill-strong"><span><b>{{ $plans->count() }}</b> rencana{{ $active > 0 ? ' · '.$active.' berjalan' : '' }}</span></span>
                    <span class="pill pill-strong"><span><b>{{ $tasksDone }}</b>/{{ $tasksTotal }} task selesai</span></span>
                    <span class="pill pill-strong"><span><b>{{ $days->count() }}</b> hari tercatat</span></span>
                </div>
            @endif
        </div>
        @if ($internship)
            <div class="pbanner-action row-actions">
                <details class="menu">
                    <summary class="btn"><x-ikon name="download" :size="18" /> Unduh</summary>
                    <div class="menu-panel">
                        <a href="{{ route('kegiatan.unduh', ['format' => 'xlsx', 'periode' => $all ? 'semua' : 'rentang', 'dari' => $period->start->toDateString(), 'sampai' => $period->end->toDateString()]) }}" class="menu-item"><x-ikon name="excel" :size="16" /> Excel (.xlsx)</a>
                        <a href="{{ route('kegiatan.unduh', ['format' => 'pdf', 'periode' => $all ? 'semua' : 'rentang', 'dari' => $period->start->toDateString(), 'sampai' => $period->end->toDateString()]) }}" target="_blank" rel="noopener" class="menu-item"><x-ikon name="print" :size="16" /> PDF (cetak)</a>
                        <p class="menu-note">{{ $all ? 'Seluruh periode KP.' : $period->label }}</p>
                        <a href="{{ route('laporan.draf') }}" target="_blank" rel="noopener" class="menu-item menu-item-sep"><x-ikon name="file" :size="16" /> Draf laporan KP</a>
                    </div>
                </details>
                @if ($canEdit)
                    <button type="button" class="btn btn-primary" data-open="dlg-plan-new"><x-ikon name="plus" :size="18" /> Rencana baru</button>
                @endif
            </div>
        @endif
    </section>

    @if (! $internship)
        <div class="card">
            <div class="empty">
                <strong>Belum bisa dipakai</strong>
                Timesheet aktif setelah pengajuan KP Anda disetujui. <a href="{{ route('kp.status') }}">Lihat status KP</a>.
            </div>
        </div>
    @else
        {{-- Dua tab: Rencana (Gantt) dan Catatan harian. Tanpa JavaScript keduanya tampil bertumpuk. --}}
        <div class="ptabs">
            <nav class="tabs" role="tablist" aria-label="Bagian timesheet" data-tabs @if (old('description') !== null) data-tab-default="catatan" @endif>
                <button type="button" class="tab is-active" role="tab" aria-selected="true" aria-controls="tab-rencana" id="tabbtn-rencana" data-tab="rencana"><x-ikon name="calendar" :size="16" /> Rencana <span class="tab-count">{{ $plans->count() }}</span></button>
                <button type="button" class="tab" role="tab" aria-selected="false" aria-controls="tab-catatan" id="tabbtn-catatan" data-tab="catatan"><x-ikon name="edit" :size="16" /> Catatan harian <span class="tab-count">{{ $entries->count() }}</span></button>
            </nav>
        </div>

        <section class="card ts-roadmap" id="tab-rencana" role="tabpanel" aria-labelledby="tabbtn-rencana" data-tab-panel="rencana">
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

        <div class="journal" id="tab-catatan" role="tabpanel" aria-labelledby="tabbtn-catatan" data-tab-panel="catatan">
            @if ($canEdit)
                {{-- Kotak cepat: tulis catatan hari ini --}}
                <section class="card jquick">
                    <form method="POST" action="{{ route('timesheet.store') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="jquick-title" for="ts-desc">Catat kegiatan: apa yang kamu kerjakan hari ini?</label>
                        <textarea id="ts-desc" name="description" class="textarea jquick-text" rows="2" maxlength="1000" required placeholder="Contoh: Membuat endpoint daftar pesanan dan menulis tesnya">{{ old('description') }}</textarea>
                        <div class="jquick-bar">
                            <label class="sr-only" for="ts-date">Tanggal</label>
                            <input id="ts-date" type="date" name="work_date" class="input input-sm jquick-date" value="{{ old('work_date', now()->min($internship->end_date)->toDateString()) }}" required>
                            <label class="sr-only" for="ts-task">Task (opsional)</label>
                            <select id="ts-task" name="weekly_task_id" class="select select-sm jquick-task">
                                <option value="">Tidak terkait task</option>
                                @foreach ($tasks as $task)
                                    <option value="{{ $task->id }}" @selected((int) old('weekly_task_id') === $task->id)>{{ $task->key() }} · {{ \Illuminate\Support\Str::limit($task->title, 50) }}</option>
                                @endforeach
                            </select>
                            <label class="btn btn-sm jquick-photo" for="ts-photo" title="Foto dokumentasi (opsional): JPG, PNG, atau WEBP, maks. 5 MB">
                                <x-ikon name="image" :size="16" /> <span data-photo-name>Foto</span>
                            </label>
                            <input id="ts-photo" type="file" name="foto" class="sr-only" accept=".jpg,.jpeg,.png,.webp" data-photo-input>
                            <button type="submit" class="btn btn-primary btn-sm jquick-save">Simpan</button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="card">
                <div class="card-head">
                    <div class="period-nav">
                        <a href="{{ route('timesheet.index', ['p' => $offset - 1]) }}#catatan" class="btn btn-sm btn-icon" aria-label="Periode sebelumnya"><x-ikon name="back" :size="18" /></a>
                        <div>
                            <h2 class="card-title">Catatan kegiatan</h2>
                            <p class="card-sub">{{ $all ? 'Seluruh periode KP' : $period->label }} · {{ $entries->count() }} catatan</p>
                        </div>
                        @if ($offset < 0)
                            <a href="{{ route('timesheet.index', ['p' => $offset + 1]) }}#catatan" class="btn btn-sm btn-icon" aria-label="Periode berikutnya"><x-ikon name="next" :size="18" /></a>
                        @endif
                    </div>
                    <div class="segmented">
                        <a href="{{ route('timesheet.index') }}#catatan" @class(['seg', 'is-active' => ! $all && $offset === 0])>Periode ini</a>
                        <a href="{{ route('timesheet.index', ['periode' => 'semua']) }}#catatan" @class(['seg', 'is-active' => $all])>Semua</a>
                    </div>
                </div>

                @if ($days->isEmpty())
                    <div class="empty">
                        <strong>Belum ada catatan</strong>
                        @if ($canEdit)
                            Tulis catatan pertama di kotak atas.
                        @else
                            Tidak ada catatan pada periode ini. Pilih <a href="{{ route('timesheet.index', ['periode' => 'semua']) }}#catatan">Semua</a> untuk melihat seluruh catatan.
                        @endif
                    </div>
                @else
                    <ol class="jdays">
                        @foreach ($days as $date => $items)
                            @php($day = \Illuminate\Support\Carbon::parse($date)->locale('id'))
                            <li @class(['jday', 'jday-'.$day->dayOfWeekIso, 'is-today' => $day->isToday()])>
                                <time class="jday-badge" datetime="{{ $day->toDateString() }}">
                                    <b>{{ $day->day }}</b>
                                    <span>{{ $day->translatedFormat('D') }}</span>
                                    <small>{{ $day->translatedFormat('M') }}</small>
                                </time>
                                <ul class="jentries">
                                    @foreach ($items as $entry)
                                        <li class="jentry">
                                            <div class="jentry-text">
                                                <p class="jentry-desc">{{ $entry->description }}</p>
                                                @if ($entry->task)
                                                    <a href="{{ route('tugas.show', $entry->task) }}" class="jentry-task" title="{{ $entry->task->title }}"><x-ikon name="tasks" :size="12" /> {{ $entry->task->key() }} · {{ \Illuminate\Support\Str::limit($entry->task->title, 40) }}</a>
                                                @endif
                                            </div>
                                            @if ($entry->photo)
                                                <a href="{{ route('timesheet.foto', $entry) }}" target="_blank" rel="noopener" class="jentry-photo" title="Foto dokumentasi">
                                                    <img src="{{ route('timesheet.foto', $entry) }}" alt="Foto dokumentasi {{ $day->translatedFormat('j M') }}" loading="lazy" width="56" height="56">
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
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
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
