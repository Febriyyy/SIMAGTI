@extends('layouts.app')

@section('title', 'Verifikasi KP Mandiri')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Verifikasi KP Mandiri</h1>
            <p class="page-sub">Periksa perusahaan sebelum ke kaprodi.</p>
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
                        <p class="card-sub">{{ $item->student->listName() }} · {{ $item->student->nim }}</p>
                    </div>
                    <x-chip tone="amber">Menunggu</x-chip>
                </div>

                <div class="card-body">
                    <dl class="kv">
                        <div><dt>Bidang</dt><dd>{{ $item->company->industry ?? '–' }}</dd></div>
                        <div><dt>Kota</dt><dd>{{ $item->company->city ?? '–' }}</dd></div>
                        <div><dt>Periode</dt><dd>{{ $item->periodLabel() }}</dd></div>
                    </dl>
                    <x-detail>
                        <dl>
                            <div><dt>Alamat</dt><dd>{{ $item->company->address ?? '–' }}@if ($item->company->city), {{ $item->company->city }}@endif</dd></div>
                            <div><dt>Kontak</dt><dd>{{ $item->company->email ?? '–' }}@if ($item->company->phone) · {{ $item->company->phone }}@endif</dd></div>
                            <div><dt>Website</dt><dd>{{ $item->company->website ?? '–' }}</dd></div>
                            <div><dt>Mahasiswa</dt><dd>{{ $item->student->name }} · {{ $item->student->nim }}</dd></div>
                            <div><dt>Jenis magang</dt><dd>{{ $item->programTypeLabel() }}{{ $item->position ? ' · '.$item->position : '' }}{{ $item->division ? ' · Divisi '.$item->division->name : '' }}</dd></div>
                            <div><dt>Company Supervisor</dt><dd>{{ $item->supervisor_name ?? '–' }}{{ $item->supervisor_title ? ' · '.$item->supervisor_title : '' }}{{ $item->supervisor_email ? ' · '.$item->supervisor_email : '' }}</dd></div>
                        </dl>
                    </x-detail>

                    <div class="decision">
                        <div class="decision-col">
                            <p class="decision-label">Perusahaan valid dan bisa dihubungi</p>
                            <form method="POST" action="{{ route('biro.verifikasi.update', $item) }}" class="inline-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="approve">
                                <button type="submit" class="btn btn-primary">Verifikasi</button>
                            </form>
                        </div>

                        <div class="decision-col">
                            <p class="decision-label">Tolak dengan alasan</p>
                            <form method="POST" action="{{ route('biro.verifikasi.update', $item) }}" class="inline-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="reject">
                                <input type="text" name="note" class="input" placeholder="Alasan penolakan" maxlength="500" required aria-label="Alasan penolakan untuk {{ $item->company->name }}">
                                <button type="submit" class="btn">Tolak</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
@endsection
