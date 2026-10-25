@extends('layouts.app')

@section('title', 'Dokumen KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Dokumen KP</h1>
            <p class="page-sub">Surat balasan dan pakta integritas mahasiswa.</p>
        </div>
        <x-chip :tone="$toReview->isEmpty() ? 'green' : 'amber'">{{ $toReview->count() }} perlu diperiksa</x-chip>
    </div>

    <section class="card">
        <div class="card-head"><h2 class="card-title">Perlu diperiksa</h2></div>
        @if ($toReview->isEmpty())
            <div class="calm"><span class="calm-icon"><x-ikon name="check" :size="20" /></span><p>Tidak ada dokumen yang menunggu diperiksa.</p></div>
        @else
            <ul class="item-list">
                @foreach ($toReview as $doc)
                    <li class="item-row doc-review">
                        <x-avatar :user="$doc->internship->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $doc->internship->student->name }}">{{ $doc->internship->student->listName() }}</div>
                            <div class="cell-sub">{{ $doc->label() }}</div>
                        </div>
                        <x-waiting :since="$doc->updated_at" />
                        <x-detail>
                            <dl>
                                <div><dt>Mahasiswa</dt><dd>{{ $doc->internship->student->name }} · {{ $doc->internship->student->nim }}</dd></div>
                                <div><dt>Perusahaan</dt><dd>{{ $doc->internship->company?->name ?? '–' }}</dd></div>
                                <div><dt>Diunggah</dt><dd>{{ $doc->updated_at->locale('id')->translatedFormat('j F Y, H.i') }}</dd></div>
                                @if ($doc->file)<div><dt>Berkas</dt><dd><a href="{{ route('dokumen.berkas', $doc) }}" target="_blank" rel="noopener"><x-ikon name="paperclip" :size="14" /> {{ $doc->file->original_name }}</a></dd></div>@endif
                            </dl>
                        </x-detail>
                        <form method="POST" action="{{ route('biro.dokumen.review', $doc) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="catatan" class="input input-sm" maxlength="500" placeholder="Alasan bila ditolak" aria-label="Catatan">
                            @if ($doc->file)<a href="{{ route('dokumen.berkas', $doc) }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost"><x-ikon name="paperclip" :size="16" /> Berkas</a>@endif
                            <button type="submit" name="keputusan" value="tolak" class="btn btn-sm">Tolak</button>
                            <button type="submit" name="keputusan" value="terima" class="btn btn-sm btn-primary"><x-ikon name="check" :size="16" /> Terima</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Belum lengkap</h2>
                <p class="card-sub">KP berjalan yang dokumennya belum diunggah atau perlu diunggah ulang</p>
            </div>
        </div>
        @if ($missing->isEmpty())
            <p class="meeting-empty">Semua dokumen KP berjalan sudah lengkap.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Mahasiswa</th><th>Dokumen</th><th>Perusahaan</th><th>Batas</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($missing as $doc)
                            <tr>
                                <td title="{{ $doc->internship->student->name }}">{{ $doc->internship->student->listName() }}</td>
                                <td>{{ $doc->label() }}</td>
                                <td>{{ $doc->internship->company?->name ?? '–' }}</td>
                                <td @class(['muted' => ! $doc->isOverdue(), 'text-red' => $doc->isOverdue()])>{{ $doc->due_on?->locale('id')->translatedFormat('j M Y') ?? '–' }}</td>
                                <td><x-chip :tone="$doc->isOverdue() ? 'red' : $doc->statusTone()">{{ $doc->isOverdue() ? 'Lewat batas' : $doc->statusLabel() }}</x-chip></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
