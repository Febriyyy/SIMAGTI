@extends('layouts.app')

@section('title', 'Laporan KP')

{{-- Menu Laporan KP mahasiswa sebelum KP berjalan --}}
@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Laporan KP</h1>
            <p class="page-sub">Tulis, unggah per bab, dan diskusikan dengan dosen pembimbing.</p>
        </div>
    </div>

    <section class="card">
        <div class="empty">
            <strong>Laporan dibuka setelah KP berjalan</strong>
            Begitu magangmu dimulai, empat bab laporan disiapkan di sini lengkap dengan draf otomatis dari data KP-mu.
            <a href="{{ route('kp.status') }}" class="btn btn-sm" style="margin-top: 14px">Lihat Status KP</a>
        </div>
    </section>
@endsection
