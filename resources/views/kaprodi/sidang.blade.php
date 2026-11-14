@extends('layouts.app')

@section('title', 'Nilai Sidang')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Nilai Sidang</h1>
            <p class="page-sub">Dicatat terpisah dari nilai akhir penilaian Company Supervisor.</p>
        </div>
        @if ($waiting->isNotEmpty())<x-chip tone="amber">{{ $waiting->count() }} menunggu</x-chip>@endif
    </div>

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Menunggu nilai sidang</h2>
                <p class="card-sub">Masa magang sudah selesai</p>
            </div>
        </div>
        @if ($waiting->isEmpty())
            <div class="calm">
                <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
                <p>Semua mahasiswa yang sudah selesai magang sudah punya nilai sidang.</p>
            </div>
        @else
            <ul class="item-list">
                @foreach ($waiting as $i)
                    <li class="item-row sidang-row">
                        <x-avatar :user="$i->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">{{ $i->student->nim }} · selesai {{ $i->end_date->locale('id')->translatedFormat('j M Y') }}@if ($i->assessment?->isValidated()) · nilai akhir {{ $i->assessment->letter_grade }}@endif</div>
                        </div>
                        <form method="POST" action="{{ route('kaprodi.sidang.simpan', $i) }}" class="sidang-form">
                            @csrf
                            @method('PUT')
                            <input type="number" name="score" class="input" min="0" max="100" step="0.01" placeholder="Nilai" required aria-label="Nilai sidang {{ $i->student->name }}">
                            <input type="date" name="defended_at" class="input" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required aria-label="Tanggal sidang {{ $i->student->name }}">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

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
                    <li class="item-row sidang-row">
                        <x-avatar :user="$i->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">Sidang {{ $d->defended_at?->locale('id')->translatedFormat('j M Y') }}@if ($i->assessment?->isValidated()) · nilai akhir {{ $i->assessment->letter_grade }}@endif</div>
                        </div>
                        <span class="sidang-score"><b>{{ $d->letter_grade }}</b>{{ \App\Support\Num::short($d->score) }}</span>
                        <x-detail label="Ubah">
                            <form method="POST" action="{{ route('kaprodi.sidang.simpan', $i) }}" class="sidang-form">
                                @csrf
                                @method('PUT')
                                <input type="number" name="score" class="input" min="0" max="100" step="0.01" value="{{ (float) $d->score }}" required aria-label="Nilai sidang {{ $i->student->name }}">
                                <input type="date" name="defended_at" class="input" value="{{ $d->defended_at?->toDateString() }}" max="{{ today()->toDateString() }}" required aria-label="Tanggal sidang {{ $i->student->name }}">
                                <input type="text" name="note" class="input" value="{{ $d->note }}" maxlength="500" placeholder="Catatan (opsional)" aria-label="Catatan sidang {{ $i->student->name }}">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </form>
                        </x-detail>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
