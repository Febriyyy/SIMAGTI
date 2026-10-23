@extends('layouts.app')

@section('title', 'Timesheet')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Timesheet</h1>
            <p class="page-sub">Catatan kegiatan harian dan jam kerja. Menjadi Form Absensi dan Aktivitas KP{{ $internship?->isMbkm() ? ' serta Logbook MBKM' : '' }}.</p>
        </div>
        @if ($internship)
            <details class="menu">
                <summary class="btn"><x-ikon name="download" :size="18" /> Unduh</summary>
                <div class="menu-panel">
                    <a href="{{ route('kegiatan.unduh', ['format' => 'xlsx', 'periode' => $all ? 'semua' : 'rentang', 'dari' => $period->start->toDateString(), 'sampai' => $period->end->toDateString()]) }}" class="menu-item"><x-ikon name="excel" :size="16" /> Excel (.xlsx)</a>
                    <a href="{{ route('kegiatan.unduh', ['format' => 'pdf', 'periode' => $all ? 'semua' : 'rentang', 'dari' => $period->start->toDateString(), 'sampai' => $period->end->toDateString()]) }}" target="_blank" rel="noopener" class="menu-item"><x-ikon name="print" :size="16" /> PDF (cetak)</a>
                    <p class="menu-note">{{ $all ? 'Seluruh periode KP.' : $period->label }}</p>
                </div>
            </details>
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
        <div class="stat-grid">
            <x-stat label="Periode ini" :value="\App\Support\Num::short((float) $entries->sum('hours'))" unit="jam" :hint="$all ? 'Seluruh periode' : $period->short" />
            <x-stat label="Hari tercatat" :value="$days->count()" unit="hari" :hint="$all ? 'Seluruh periode' : $period->short" />
            @if ($targetHours)
                <x-stat label="Target logbook MBKM" :value="\App\Support\Num::short($totalHours)" :unit="'/ '.$targetHours.' jam'" :percent="min(100, (int) round($totalHours / $targetHours * 100))" />
            @else
                <x-stat label="Total jam KP" :value="\App\Support\Num::short($totalHours)" unit="jam" hint="Sejak awal KP" />
            @endif
        </div>

        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div class="period-nav">
                            <a href="{{ route('timesheet.index', ['p' => $offset - 1]) }}" class="btn btn-sm btn-icon" aria-label="Periode sebelumnya"><x-ikon name="back" :size="18" /></a>
                            <div>
                                <h2 class="card-title">{{ $all ? 'Seluruh periode KP' : $period->label }}</h2>
                                <p class="card-sub">{{ $entries->count() }} catatan · {{ \App\Support\Num::short((float) $entries->sum('hours')) }} jam</p>
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
                                        <span class="day-total">{{ \App\Support\Num::short((float) $items->sum('hours')) }} jam</span>
                                    </div>
                                    <ul class="time-list">
                                        @foreach ($items as $entry)
                                            <li>
                                                <span class="time-hours">{{ \App\Support\Num::short((float) $entry->hours) }} j</span>
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
                                <div class="form-grid">
                                    <div>
                                        <label class="label" for="ts-date">Tanggal</label>
                                        <input id="ts-date" type="date" name="work_date" class="input" value="{{ old('work_date', now()->min($internship->end_date)->toDateString()) }}" required>
                                    </div>
                                    <div>
                                        <label class="label" for="ts-hours">Durasi (jam)</label>
                                        <input id="ts-hours" type="number" name="hours" class="input" min="0.25" max="16" step="0.25" value="{{ old('hours') }}" required placeholder="8">
                                    </div>
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
                                    <p class="hint">JPG/PNG/WEBP, maks. 5 MB{{ $internship->isMbkm() ? '. Logbook MBKM meminta foto dokumentasi.' : '.' }}</p>
                                </div>
                                <div><button type="submit" class="btn btn-primary"><x-ikon name="timer" :size="18" /> Simpan catatan</button></div>
                            </form>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    @endif
@endsection
