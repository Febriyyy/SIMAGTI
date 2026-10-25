@extends('layouts.app')

@section('title', 'Rekognisi SKS')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Rekognisi SKS</h1>
            <p class="page-sub">Maksimal {{ \App\Models\CreditRecognition::MAX_CREDITS }} SKS per mahasiswa.</p>
        </div>
        <x-chip :tone="$pending->isEmpty() ? 'green' : 'amber'">{{ $pending->count() }} menunggu</x-chip>
    </div>

    <section class="card">
        <div class="card-head"><h2 class="card-title">Menunggu keputusan</h2></div>
        @if ($pending->isEmpty())
            <div class="calm"><span class="calm-icon"><x-ikon name="check" :size="20" /></span><p>Tidak ada usulan rekognisi.</p></div>
        @else
            <ul class="item-list">
                @foreach ($pending as $cr)
                    <li class="item-row change">
                        <x-avatar :user="$cr->internship->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $cr->internship->student->name }}">{{ $cr->internship->student->listName() }}</div>
                            <div class="cell-sub">{{ $cr->totalCredits() }} SKS · {{ count($cr->courses) }} mata kuliah</div>
                        </div>
                        <x-waiting :since="$cr->updated_at" />
                        <x-detail>
                            <dl>
                                <div><dt>Mahasiswa</dt><dd>{{ $cr->internship->student->name }} · {{ $cr->internship->student->nim }}</dd></div>
                                <div><dt>Tempat</dt><dd>{{ $cr->internship->company?->name }} · {{ $cr->internship->periodLabel() }}</dd></div>
                                <div><dt>Mata kuliah</dt><dd>@foreach ($cr->courses as $c){{ $c['code'] ? $c['code'].' · ' : '' }}{{ $c['name'] }} ({{ $c['credits'] }} SKS)@if (! $loop->last)<br>@endif @endforeach</dd></div>
                            </dl>
                        </x-detail>
                        <form method="POST" action="{{ route('rekognisi.decide', $cr) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="catatan" class="input input-sm" maxlength="500" placeholder="Alasan bila ditolak" aria-label="Catatan">
                            <button type="submit" name="keputusan" value="tolak" class="btn btn-sm">Tolak</button>
                            <button type="submit" name="keputusan" value="setujui" class="btn btn-sm btn-primary"><x-ikon name="check" :size="16" /> Setujui</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($decided->isNotEmpty())
        <section class="card">
            <div class="card-head"><h2 class="card-title">Sudah diputuskan</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Mahasiswa</th><th>SKS</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        @foreach ($decided as $cr)
                            <tr>
                                <td title="{{ $cr->internship->student->name }}">{{ $cr->internship->student->listName() }}</td>
                                <td>{{ $cr->totalCredits() }}</td>
                                <td><x-chip :tone="$cr->statusTone()">{{ $cr->statusLabel() }}</x-chip></td>
                                <td class="muted">{{ $cr->decided_at?->locale('id')->translatedFormat('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
