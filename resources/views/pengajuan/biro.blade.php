@extends('layouts.app')

@section('title', 'Verifikasi KP Mandiri')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Verifikasi KP Mandiri</h1>
            <p class="page-sub">Periksa perusahaan yang diajukan mahasiswa sebelum diteruskan ke Kaprodi.</p>
        </div>
    </div>

    @if ($waiting->isEmpty())
        <div class="card">
            <div class="empty">
                <strong>Tidak ada pengajuan yang menunggu</strong>
                Pengajuan baru dari mahasiswa akan muncul di sini.
            </div>
        </div>
    @else
        @foreach ($waiting as $item)
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">{{ $item->company->name }}</h2>
                        <p class="card-sub">Diajukan oleh {{ $item->student->name }} ({{ $item->student->nim }})</p>
                    </div>
                    <x-chip tone="amber">Menunggu verifikasi</x-chip>
                </div>

                <div class="card-body">
                    <dl class="kv">
                        <div><dt>Bidang</dt><dd>{{ $item->company->industry ?? '–' }}</dd></div>
                        <div><dt>Alamat</dt><dd>{{ $item->company->address ?? '–' }}@if ($item->company->city), {{ $item->company->city }}@endif</dd></div>
                        <div><dt>Kontak</dt><dd>{{ $item->company->email ?? '–' }}@if ($item->company->phone) · {{ $item->company->phone }}@endif</dd></div>
                        <div><dt>Website</dt><dd>{{ $item->company->website ?? '–' }}</dd></div>
                        <div><dt>Divisi</dt><dd>{{ $item->division?->name ?? '–' }}</dd></div>
                        <div><dt>Periode</dt><dd>{{ $item->start_date->locale('id')->translatedFormat('j F Y') }} – {{ $item->end_date->locale('id')->translatedFormat('j F Y') }}</dd></div>
                    </dl>

                    <div class="toolbar">
                        <form method="POST" action="{{ route('biro.verifikasi.update', $item) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="approve">
                            <button type="submit" class="btn btn-primary btn-sm">Verifikasi</button>
                        </form>

                        <form method="POST" action="{{ route('biro.verifikasi.update', $item) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="reject">
                            <input type="text" name="note" class="input" placeholder="Alasan penolakan" maxlength="500" required aria-label="Alasan penolakan untuk {{ $item->company->name }}">
                            <button type="submit" class="btn btn-sm">Tolak</button>
                        </form>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
@endsection
