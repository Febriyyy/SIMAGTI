@extends('layouts.app')

@section('title', 'Surat Pengantar')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Surat Pengantar</h1>
            <p class="page-sub">Nomor surat dibuat otomatis.</p>
        </div>
        <x-chip :tone="$requests->isEmpty() ? 'green' : 'amber'">{{ $requests->count() }} permintaan</x-chip>
    </div>

    <section class="card">
        <div class="card-head"><h2 class="card-title">Menunggu diterbitkan</h2></div>
        @if ($requests->isEmpty())
            <div class="calm"><span class="calm-icon"><x-ikon name="check" :size="20" /></span><p>Tidak ada permintaan surat pengantar.</p></div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Mahasiswa</th><th>Perusahaan</th><th>Periode</th><th>Menunggu</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($requests as $doc)
                            <tr>
                                <td><x-person :user="$doc->internship->student" /></td>
                                <td>{{ $doc->internship->company?->name ?? '–' }}</td>
                                <td class="muted">{{ $doc->internship->periodLabel() }}</td>
                                <td><x-waiting :since="$doc->created_at" /></td>
                                <td class="cell-actions">
                                    <form method="POST" action="{{ route('tu.surat.terbit', $doc) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-primary"><x-ikon name="send" :size="16" /> Terbitkan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($issued->isNotEmpty())
        <section class="card">
            <div class="card-head"><h2 class="card-title">Baru diterbitkan</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Nomor</th><th>Mahasiswa</th><th>Perusahaan</th><th>Terbit</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($issued as $doc)
                            <tr>
                                <td class="cell-title">{{ $doc->number }}</td>
                                <td title="{{ $doc->internship->student->name }}">{{ $doc->internship->student->listName() }}</td>
                                <td>{{ $doc->internship->company?->name ?? '–' }}</td>
                                <td class="muted">{{ $doc->processed_at?->locale('id')->translatedFormat('j M Y') }}</td>
                                <td class="cell-actions"><a href="{{ route('dokumen.cetak', $doc) }}" target="_blank" rel="noopener" class="btn btn-sm"><x-ikon name="print" :size="16" /> Cetak</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
