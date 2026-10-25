@extends('layouts.app')

@section('title', 'Evaluasi Bulanan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Evaluasi Bulanan</h1>
            <p class="page-sub">Refleksi tiap akhir bulan, lalu dinilai 1–4.</p>
        </div>
    </div>

    @if (! $internship)
        <div class="card"><div class="empty"><strong>Belum ada KP berjalan</strong> Evaluasi bulanan muncul setelah KP Anda dimulai.</div></div>
    @elseif ($months === [])
        <div class="card"><div class="empty"><strong>KP belum dimulai</strong> Bulan pertama dimulai {{ $internship->start_date->locale('id')->translatedFormat('j F Y') }}.</div></div>
    @else
        @foreach ($months as $entry)
            @include('bulanan._month', ['entry' => $entry, 'canEdit' => $canEdit])
        @endforeach
    @endif
@endsection
