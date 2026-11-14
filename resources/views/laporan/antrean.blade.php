@extends('layouts.app')

@section('title', 'Laporan KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Laporan KP</h1>
            <p class="page-sub">{{ $isSupervisor ? 'Hanya bab yang memuat informasi perusahaan.' : 'Bab berisi data perusahaan sampai di sini setelah disetujui Company Supervisor.' }}</p>
        </div>
        @if ($queue->isNotEmpty())<x-chip tone="amber">{{ $queue->count() }} perlu ditinjau</x-chip>@endif
    </div>

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Perlu ditinjau</h2>
                <p class="card-sub">{{ $isSupervisor ? 'Pastikan tidak ada informasi rahasia perusahaan' : 'Setujui atau minta revisi' }}</p>
            </div>
        </div>
        @if ($queue->isEmpty())
            <div class="calm">
                <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
                <p>Tidak ada bab yang menunggu tinjauan Anda.</p>
            </div>
        @else
            <ul class="item-list">
                @foreach ($queue as $chapter)
                    <li class="item-row review-row">
                        <x-avatar :user="$chapter->internship->student" />
                        <div class="grow min0">
                            <div class="cell-title">{{ $chapter->label() }}</div>
                            <div class="cell-sub">{{ $chapter->internship->student->listName() }} · versi {{ $chapter->version }}</div>
                        </div>
                        <x-waiting :since="$chapter->submitted_at" />
                        <a href="{{ route('laporan.berkas', $chapter) }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost"><x-ikon name="paperclip" :size="16" /> Buka</a>
                        <form method="POST" action="{{ route('laporan.tinjau', $chapter) }}" class="review-form">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="catatan" class="input" maxlength="1000" placeholder="Catatan revisi" aria-label="Catatan revisi {{ $chapter->label() }}">
                            <button type="submit" name="keputusan" value="revisi" class="btn">Minta revisi</button>
                            <button type="submit" name="keputusan" value="setujui" class="btn btn-primary"><x-ikon name="check" :size="16" /> Setujui</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Progres laporan</h2>
                <p class="card-sub">{{ $internships->count() }} mahasiswa</p>
            </div>
        </div>
        @if ($internships->isEmpty())
            <div class="empty"><strong>Belum ada mahasiswa</strong> Progres muncul setelah KP berjalan.</div>
        @else
            <ul class="item-list">
                @foreach ($internships as $i)
                    @php($chapters = $i->reportChapters)
                    <li class="item-row">
                        <x-avatar :user="$i->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                            <div class="cell-sub">{{ $chapters->isEmpty() ? 'Belum mulai' : $chapters->where('status', 'disetujui')->count().' dari '.$chapters->count().' bab disetujui' }}</div>
                        </div>
                        @if ($chapters->isNotEmpty())
                            <ol class="chapter-dots" aria-label="Status per bab">
                                @foreach ($chapters as $chapter)
                                    <li class="tone-{{ $chapter->statusTone() }}" title="{{ $chapter->label() }}: {{ $chapter->statusLabel() }}"><span class="sr-only">{{ $chapter->label() }}: {{ $chapter->statusLabel() }}</span>{{ $chapter->position }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
