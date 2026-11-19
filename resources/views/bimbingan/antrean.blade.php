@extends('layouts.app')

@section('title', 'Bimbingan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Bimbingan</h1>
            <p class="page-sub">Minimal {{ $min }} sesi per mahasiswa.</p>
        </div>
        <div class="row-actions">
            <x-chip :tone="$pending->isEmpty() ? 'green' : 'amber'">{{ $pending->count() }} menunggu</x-chip>
            @if ($internships->isNotEmpty())
                <button type="button" class="btn btn-primary" data-open="dlg-gsched"><x-ikon name="calendar" :size="18" /> Jadwalkan bimbingan</button>
            @endif
        </div>
    </div>

    <div class="layout">
        <div class="col">
            @if ($scheduled->isNotEmpty())
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Jadwal bimbingan</h2>
                            <p class="card-sub">Tandai selesai setelah berlangsung agar terhitung</p>
                        </div>
                    </div>
                    @include('bimbingan._scheduled', ['sessions' => $scheduled])
                </section>
            @endif
            <section class="card">
                <div class="card-head"><h2 class="card-title">Menunggu konfirmasi</h2></div>
                @if ($pending->isEmpty())
                    <div class="calm"><span class="calm-icon"><x-ikon name="check" :size="20" /></span><p>Semua bimbingan sudah dikonfirmasi.</p></div>
                @else
                    @include('bimbingan._list', ['sessions' => $pending, 'canDecide' => true, 'showStudent' => true])
                @endif
            </section>
        </div>
        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Rekap per mahasiswa</h2></div>
                @if ($internships->isEmpty())
                    <p class="meeting-empty">Belum ada mahasiswa bimbingan.</p>
                @else
                    <ul class="item-list">
                        @foreach ($internships as $i)
                            {{-- Ringkas: nama + deret sesi (terisi = terkonfirmasi). Tempat dan tanggal selesai ada di tooltip dan halaman pantau. --}}
                            @php($done = $i->confirmed_count >= $min)
                            <li>
                                <a href="{{ route('pantau.show', [$i, 'tab' => 'bimbingan']) }}" class="item-row row-link recap-row"
                                   title="{{ $i->student->name }} · {{ $i->placeName() }} · s.d. {{ $i->end_date->locale('id')->translatedFormat('j M Y') }}">
                                    <x-avatar :user="$i->student" />
                                    <div class="grow min0">
                                        <div class="cell-title recap-name">{{ $i->student->listName() }}</div>
                                        <span class="recap-dots" aria-hidden="true">
                                            @for ($n = 1; $n <= $min; $n++)<i @class(['is-on' => $n <= $i->confirmed_count])></i>@endfor
                                        </span>
                                    </div>
                                    <span @class(['recap-count', 'is-done' => $done]) aria-label="{{ $i->confirmed_count }} dari {{ $min }} sesi terkonfirmasi">@if ($done)<x-ikon name="tick" :size="12" />@endif{{ $i->confirmed_count }}/{{ $min }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
    @if ($internships->isNotEmpty())
        <?php $bag = $errors->getBag('jadwal'); ?>
        <dialog id="dlg-gsched" class="dialog" @if ($bag->any()) data-autoopen @endif>
            <form method="POST" action="{{ route('bimbingan.jadwal') }}" class="dialog-body" data-gsched>
                @csrf
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Jadwalkan bimbingan</h2>
                        <p class="card-sub">Mahasiswa mendapat notifikasi dan tombol Gabung</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div class="form-grid">
                    <div class="span-all">
                        <label class="label" for="gs-student">Mahasiswa</label>
                        <select id="gs-student" name="internship_id" class="select" required>
                            @foreach ($internships as $i)
                                <option value="{{ $i->id }}" @selected((int) old('internship_id') === $i->id)>{{ $i->student->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="gs-date">Tanggal</label>
                        <input id="gs-date" type="date" name="held_on" class="input" required min="{{ today()->toDateString() }}" value="{{ old('held_on', today()->addDay()->toDateString()) }}">
                        @if ($bag->has('held_on'))<p class="field-error">{{ $bag->first('held_on') }}</p>@endif
                    </div>
                    <div>
                        <label class="label" for="gs-time">Jam</label>
                        <input id="gs-time" type="time" name="start_time" class="input" required value="{{ old('start_time', '10:00') }}">
                    </div>
                    <div class="span-all">
                        <label class="label" for="gs-topic">Topik</label>
                        <input id="gs-topic" type="text" name="topic" class="input" maxlength="200" required placeholder="Contoh: Revisi bab 3" value="{{ old('topic') }}">
                        @if ($bag->has('topic'))<p class="field-error">{{ $bag->first('topic') }}</p>@endif
                    </div>
                    <div>
                        <label class="label" for="gs-mode">Cara</label>
                        <select id="gs-mode" name="mode" class="select" data-gsched-mode>
                            @foreach (\App\Models\GuidanceSession::MODES as $k => $v)
                                <option value="{{ $k }}" @selected(old('mode', 'daring') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div data-gsched-link>
                        <label class="label" for="gs-link">Tautan Zoom atau Meet</label>
                        <input id="gs-link" type="url" name="meeting_link" class="input" maxlength="300" placeholder="https://meet.google.com/..." value="{{ old('meeting_link') }}">
                        @if ($bag->has('meeting_link'))<p class="field-error">{{ $bag->first('meeting_link') }}</p>@endif
                    </div>
                    <div class="span-all">
                        <label class="label" for="gs-notes">Pesan untuk mahasiswa <span class="label-opt">(opsional)</span></label>
                        <textarea id="gs-notes" name="notes" class="textarea" rows="2" maxlength="2000" placeholder="Contoh: Siapkan berkas revisi bab 3">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim jadwal</button>
                </div>
            </form>
        </dialog>
        <script>
            (function () {
                var mode = document.querySelector('[data-gsched-mode]');
                var box = document.querySelector('[data-gsched-link]');
                if (!mode || !box) return;
                var input = box.querySelector('input');
                var sync = function () {
                    var online = mode.value === 'daring';
                    box.hidden = !online;
                    input.required = online;
                    if (!online) input.value = '';
                };
                mode.addEventListener('change', sync);
                sync();
            })();
        </script>
    @endif
@endsection
