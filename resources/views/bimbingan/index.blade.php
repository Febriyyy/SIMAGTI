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
        <section class="gtrack" aria-labelledby="gtrack-title">
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
