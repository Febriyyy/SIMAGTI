@extends('layouts.app')

@section('title', 'Bimbingan')

@section('content')
    @php($confirmed = $sessions->where('status', 'confirmed')->count())
    @php($pending = $sessions->where('status', 'pending')->count())
    <div class="page-head">
        <div>
            <h1 class="page-title">Bimbingan</h1>
            <p class="page-sub">Minimal {{ $min }} sesi terkonfirmasi dosen.</p>
        </div>
        @if ($internship && $canEdit)
            {{-- Di layar sempit formulir dibuka lewat dialog; di desktop formulir ada di kolom kanan --}}
            <button type="button" class="btn btn-primary gform-open" data-open="dlg-guidance"><x-ikon name="plus" :size="18" /> Catat bimbingan</button>
        @endif
    </div>

    @if (! $internship)
        <div class="card"><div class="empty"><strong>Belum ada KP berjalan</strong> Log bimbingan tersedia setelah KP dimulai.</div></div>
    @else
        {{-- Pelacak syarat: satu kotak per sesi yang dibutuhkan --}}
        @php($slots = max($min, $confirmed))
        <section @class(['gtrack', 'is-complete' => $confirmed >= $min]) aria-labelledby="gtrack-title" @if ($confirmed >= $min) data-celebrate="bimbingan-{{ $internship->id }}" @endif>
            <div class="gtrack-text">
                <h2 id="gtrack-title" class="gtrack-title">
                    @if ($confirmed >= $min)
                        Syarat bimbingan terpenuhi
                    @else
                        {{ $min - $confirmed }} sesi lagi untuk syarat hasil KP
                    @endif
                </h2>
                <p class="gtrack-sub">
                    <x-chip :tone="$confirmed >= $min ? 'green' : 'amber'">{{ $confirmed }} / {{ $min }} terkonfirmasi</x-chip>
                    @if ($pending > 0)<span>{{ $pending }} menunggu konfirmasi {{ $internship->lecturer?->listName() ?? 'dosen pembimbing' }}</span>@else<span>Dengan {{ $internship->lecturer?->listName() ?? 'dosen pembimbing' }}</span>@endif
                </p>
            </div>
            <ol class="gtrack-slots" style="--slots: {{ $slots }}" aria-label="{{ $confirmed }} dari {{ $min }} sesi terkonfirmasi">
                @for ($n = 1; $n <= $slots; $n++)
                    @php($state = $n <= $confirmed ? 'done' : ($n <= $confirmed + $pending ? 'wait' : 'todo'))
                    <li class="gtrack-slot is-{{ $state }}">
                        @if ($state === 'done')<x-ikon name="tick" :size="16" />@elseif ($state === 'wait')<x-ikon name="clock" :size="16" />@else<span>{{ $n }}</span>@endif
                        <span class="sr-only">Sesi {{ $n }}: {{ ['done' => 'dikonfirmasi', 'wait' => 'menunggu konfirmasi', 'todo' => 'belum ada'][$state] }}</span>
                    </li>
                @endfor
            </ol>
        </section>

        @php($next = $sessions->where('status', 'scheduled')->filter(fn ($s) => $s->held_on->gte(today()))->sortBy(fn ($s) => $s->startsAt())->first())
        @if ($next)
            <section class="gnext" aria-label="Bimbingan berikutnya">
                <time class="gnext-date" datetime="{{ $next->startsAt()->toIso8601String() }}">
                    <b>{{ $next->held_on->format('j') }}</b>
                    <span>{{ $next->held_on->locale('id')->translatedFormat('M') }}</span>
                </time>
                <div class="gnext-text min0">
                    <p class="gnext-kicker">{{ $next->held_on->isToday() ? 'Hari ini' : $next->held_on->locale('id')->translatedFormat('l') }}{{ $next->timeLabel() ? ', '.$next->timeLabel() : '' }} · {{ $next->modeLabel() }}</p>
                    <h2 class="gnext-title">{{ $next->topic }}</h2>
                    <p class="gnext-sub">Dijadwalkan {{ $internship->lecturer?->listName() ?? 'dosen pembimbing' }}</p>
                </div>
                <div class="gnext-actions">
                    <a href="{{ \App\Support\CalendarLink::guidance($next) }}" target="_blank" rel="noopener noreferrer" class="btn">
                        <x-ikon name="calendar" :size="18" /> Tambah ke Google Calendar
                    </a>
                    @if ($next->canJoin())
                        <a href="{{ $next->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary gnext-join"><x-ikon name="video" :size="18" /> Gabung</a>
                    @endif
                </div>
            </section>
        @endif

        <div class="layout gbim">
            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Riwayat bimbingan</h2>
                            <p class="card-sub">Dengan {{ $internship->lecturer?->listName() ?? 'dosen pembimbing' }} · {{ $sessions->count() }} sesi</p>
                        </div>
                    </div>
                    @include('bimbingan._timeline', ['sessions' => $sessions, 'canDelete' => $canEdit])
                </section>
            </div>
            @if ($canEdit)
                <div class="col gform-aside">
                    <section class="card">
                        <div class="card-head"><h2 class="card-title">Catat bimbingan</h2></div>
                        <div class="card-body">@include('bimbingan._form', ['prefix' => 'g'])</div>
                    </section>
                </div>

                <dialog id="dlg-guidance" class="dialog" @if ($errors->any() && old('_dlg')) data-autoopen @endif>
                    <div class="dialog-body">
                        <div class="dialog-head">
                            <h2 class="card-title">Catat bimbingan</h2>
                            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                        </div>
                        @include('bimbingan._form', ['prefix' => 'gd'])
                    </div>
                </dialog>
            @endif
        </div>
    @endif
@endsection
