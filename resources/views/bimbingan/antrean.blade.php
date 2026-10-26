@extends('layouts.app')

@section('title', 'Bimbingan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Bimbingan</h1>
            <p class="page-sub">Minimal {{ $min }} sesi per mahasiswa.</p>
        </div>
        <x-chip :tone="$pending->isEmpty() ? 'green' : 'amber'">{{ $pending->count() }} menunggu</x-chip>
    </div>

    <div class="layout">
        <div class="col">
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
                            <li>
                                <a href="{{ route('pantau.show', [$i, 'tab' => 'bimbingan']) }}" class="item-row row-link">
                                    <x-avatar :user="$i->student" />
                                    <div class="grow min0">
                                        <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                                        <div class="cell-sub">{{ $i->placeName() }} · s.d. {{ $i->end_date->locale('id')->translatedFormat('j M Y') }}</div>
                                    </div>
                                    <x-chip :tone="$i->confirmed_count >= $min ? 'green' : 'gray'">{{ $i->confirmed_count }} / {{ $min }}</x-chip>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
