@extends('layouts.app')

@section('title', 'Sidang KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Sidang KP</h1>
            <p class="page-sub">Jadwalkan sidang, lalu isi nilainya. Nilai sidang dicatat terpisah dari nilai akhir.</p>
        </div>
    </div>

    {{-- 1. Belum dijadwalkan --}}
    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Perlu dijadwalkan</h2>
                <p class="card-sub">KP selesai atau berakhir dalam 30 hari</p>
            </div>
            @if ($unscheduled->isNotEmpty())<x-chip tone="amber">{{ $unscheduled->count() }}</x-chip>@endif
        </div>
        @if ($unscheduled->isEmpty())
            <div class="calm">
                <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
                <p>Semua mahasiswa yang perlu sidang sudah dijadwalkan.</p>
            </div>
        @else
            <ul class="item-list">
                @foreach ($unscheduled as $i)
                    <li class="item-row defense-row">
                        <x-avatar :user="$i->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">{{ $i->status === 'selesai' ? 'Selesai' : 'Berakhir' }} {{ $i->end_date->locale('id')->translatedFormat('j M Y') }} · {{ $i->lecturer?->listName() ?? 'Dosen belum ditunjuk' }}</div>
                        </div>
                        <div class="defense-actions">
                            @if (\App\Actions\Results\RecordDefense::eligible($i))
                                <button type="button" class="btn btn-sm btn-ghost" data-open="dlg-score-{{ $i->id }}">Isi nilai</button>
                            @endif
                            <button type="button" class="btn btn-sm btn-primary" data-open="dlg-sch-{{ $i->id }}"><x-ikon name="calendar" :size="16" /> Jadwalkan</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- 2. Terjadwal --}}
    @if ($scheduled->isNotEmpty())
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Jadwal sidang</h2>
                    <p class="card-sub">Beri nilai setelah sidang berlangsung</p>
                </div>
            </div>
            <ul class="item-list">
                @foreach ($scheduled as $i)
                    @php($s = $i->defenseSchedule)
                    <li class="item-row defense-row">
                        <span class="defense-date" aria-hidden="true">
                            <b>{{ $s->scheduled_at->format('j') }}</b>
                            <small>{{ $s->scheduled_at->locale('id')->translatedFormat('M') }}</small>
                        </span>
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">
                                <span class="sr-only">{{ $s->whenLabel() }} ·</span>{{ $s->scheduled_at->locale('id')->translatedFormat('l') }}, {{ $s->scheduled_at->format('H.i') }}
                                · {{ $s->isOnline() ? 'Daring' : $s->place }}
                                @if ($s->examiner) · Penguji {{ $s->examiner->listName() }}@endif
                            </div>
                        </div>
                        <div class="defense-actions">
                            @if ($s->hasPassed())
                                <button type="button" class="btn btn-sm btn-ghost" data-open="dlg-sch-{{ $i->id }}">Ubah jadwal</button>
                                <button type="button" class="btn btn-sm btn-primary" data-open="dlg-score-{{ $i->id }}">Beri nilai</button>
                            @else
                                <span class="defense-soon">{{ ($d = (int) today()->diffInDays($s->scheduled_at->copy()->startOfDay())) === 0 ? 'Hari ini' : $d.' hari lagi' }}</span>
                                <button type="button" class="btn btn-sm btn-ghost" data-open="dlg-sch-{{ $i->id }}">Ubah jadwal</button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 3. Sudah dinilai --}}
    @if ($done->isNotEmpty())
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Sudah dinilai</h2>
                    <p class="card-sub">{{ $done->count() }} mahasiswa</p>
                </div>
            </div>
            <ul class="item-list">
                @foreach ($done as $i)
                    @php($d = $i->defenseScore)
                    <li class="item-row defense-row">
                        <x-avatar :user="$i->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">Sidang {{ $d->defended_at?->locale('id')->translatedFormat('j M Y') }}@if ($i->assessment?->isValidated()) · nilai akhir {{ $i->assessment->letter_grade }}@endif</div>
                        </div>
                        <span class="sidang-score"><b>{{ $d->letter_grade }}</b>{{ \App\Support\Num::short($d->score) }}</span>
                        <button type="button" class="btn btn-sm btn-ghost" data-open="dlg-score-{{ $i->id }}">Ubah</button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Dialog jadwal --}}
    @foreach ($unscheduled->concat($scheduled) as $i)
        <?php $s = $i->defenseSchedule; $bag = $errors->getBag('jadwal'.$i->id); ?>
        <dialog id="dlg-sch-{{ $i->id }}" class="dialog" @if ($bag->any()) data-autoopen @endif>
            <form method="POST" action="{{ route('kaprodi.sidang.jadwal', $i) }}" class="dialog-body">
                @csrf
                @method('PUT')
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">{{ $s ? 'Ubah jadwal sidang' : 'Jadwalkan sidang' }}</h2>
                        <p class="card-sub">{{ $i->student->name }}</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="label" for="sch-date-{{ $i->id }}">Tanggal</label>
                        <input id="sch-date-{{ $i->id }}" type="date" name="tanggal" class="input" required min="{{ today()->toDateString() }}" value="{{ old('tanggal', $s?->scheduled_at->toDateString()) }}">
                        @if ($bag->has('tanggal'))<p class="field-error">{{ $bag->first('tanggal') }}</p>@endif
                    </div>
                    <div>
                        <label class="label" for="sch-time-{{ $i->id }}">Jam</label>
                        <input id="sch-time-{{ $i->id }}" type="time" name="jam" class="input" required value="{{ old('jam', $s?->scheduled_at->format('H:i') ?? '09:00') }}">
                    </div>
                    <div>
                        <label class="label" for="sch-mode-{{ $i->id }}">Cara</label>
                        <select id="sch-mode-{{ $i->id }}" name="cara" class="select">
                            @foreach (\App\Models\DefenseSchedule::MODES as $k => $label)
                                <option value="{{ $k }}" @selected(old('cara', $s?->mode ?? 'luring') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="sch-ex-{{ $i->id }}">Dosen penguji <span class="muted">(opsional)</span></label>
                        <select id="sch-ex-{{ $i->id }}" name="penguji" class="select">
                            <option value="">Belum ditentukan</option>
                            @foreach ($examiners as $ex)
                                <option value="{{ $ex->id }}" @selected((int) old('penguji', $s?->examiner_id) === $ex->id)>{{ $ex->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="span-all">
                        <label class="label" for="sch-place-{{ $i->id }}">Ruang atau tautan rapat</label>
                        <input id="sch-place-{{ $i->id }}" type="text" name="tempat" class="input" maxlength="200" required placeholder="Ruang B2.04 atau https://meet.google.com/..." value="{{ old('tempat', $s?->place) }}">
                        @if ($bag->has('tempat'))<p class="field-error">{{ $bag->first('tempat') }}</p>@endif
                    </div>
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan dan beri tahu</button>
                </div>
            </form>
        </dialog>
    @endforeach

    {{-- Dialog nilai --}}
    @foreach ($unscheduled->filter(fn ($i) => \App\Actions\Results\RecordDefense::eligible($i))->concat($scheduled->filter(fn ($i) => $i->defenseSchedule->hasPassed()))->concat($done) as $i)
        <?php $d = $i->defenseScore; $s = $i->defenseSchedule; ?>
        <dialog id="dlg-score-{{ $i->id }}" class="dialog">
            <form method="POST" action="{{ route('kaprodi.sidang.simpan', $i) }}" class="dialog-body">
                @csrf
                @method('PUT')
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Nilai sidang</h2>
                        <p class="card-sub">{{ $i->student->name }}</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="label" for="sc-val-{{ $i->id }}">Nilai (0–100)</label>
                        <input id="sc-val-{{ $i->id }}" type="number" name="score" class="input" min="0" max="100" step="0.01" required value="{{ $d ? (float) $d->score : '' }}">
                    </div>
                    <div>
                        <label class="label" for="sc-date-{{ $i->id }}">Tanggal sidang</label>
                        <input id="sc-date-{{ $i->id }}" type="date" name="defended_at" class="input" required max="{{ today()->toDateString() }}" value="{{ $d?->defended_at?->toDateString() ?? $s?->scheduled_at->toDateString() ?? today()->toDateString() }}">
                    </div>
                    <div class="span-all">
                        <label class="label" for="sc-note-{{ $i->id }}">Catatan <span class="muted">(opsional)</span></label>
                        <input id="sc-note-{{ $i->id }}" type="text" name="note" class="input" maxlength="500" value="{{ $d?->note }}">
                    </div>
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan nilai</button>
                </div>
            </form>
        </dialog>
    @endforeach
@endsection
