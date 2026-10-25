@extends('layouts.app')

@section('title', 'Pengajuan Perubahan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ $step === 'tu' ? 'Ganti Company Supervisor' : 'Pengajuan Perubahan' }}</h1>
            <p class="page-sub">{{ ['supervisor' => 'Cukup setuju atau tidak, kaprodi yang memutuskan.', 'tu' => 'Setelah disetujui, aktifkan supervisor baru.'][$step] ?? 'Perpanjangan, pindah, dan pengunduran diri.' }}</p>
        </div>
        <x-chip :tone="$pending->isEmpty() ? 'green' : 'amber'">{{ $pending->count() }} menunggu</x-chip>
    </div>

    <section class="card">
        <div class="card-head"><h2 class="card-title">Menunggu keputusan</h2></div>
        @if ($pending->isEmpty())
            <div class="calm"><span class="calm-icon"><x-ikon name="check" :size="20" /></span><p>Tidak ada pengajuan yang menunggu.</p></div>
        @else
            <ul class="item-list">
                @foreach ($pending as $c)
                    <li class="item-row change">
                        <x-avatar :user="$c->internship->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $c->internship->student->name }}">{{ $c->internship->student->listName() }}</div>
                            <div class="cell-sub">{{ $c->typeLabel() }}</div>
                        </div>
                        <x-waiting :since="$c->updated_at" />
                        <x-detail>
                            <dl>
                                <div><dt>Mahasiswa</dt><dd>{{ $c->internship->student->name }} · {{ $c->internship->student->nim }}</dd></div>
                                <div><dt>Tempat</dt><dd>{{ $c->internship->placeName() }} · {{ $c->internship->periodLabel() }}</dd></div>
                                <div><dt>Perubahan</dt><dd>{{ $c->summary() }}</dd></div>
                                <div><dt>Alasan</dt><dd>{{ $c->reason }}</dd></div>
                                @if ($c->supervisor_note)<div><dt>Company Supervisor</dt><dd>{{ $c->supervisor_note }}</dd></div>@endif
                                @if ($c->requester && $c->requester->id !== $c->internship->student_id)<div><dt>Diajukan oleh</dt><dd>{{ $c->requester->name }}</dd></div>@endif
                            </dl>
                        </x-detail>
                        <form method="POST" action="{{ route('perubahan.decide', $c) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="catatan" class="input input-sm" maxlength="500" placeholder="Catatan (wajib bila ditolak)" aria-label="Catatan">
                            <button type="submit" name="keputusan" value="tolak" class="btn btn-sm">Tolak</button>
                            <button type="submit" name="keputusan" value="setujui" class="btn btn-sm btn-primary"><x-ikon name="check" :size="16" /> {{ $step === 'supervisor' ? 'Setuju' : 'Setujui' }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($recent->isNotEmpty())
        <section class="card">
            <div class="card-head"><h2 class="card-title">Baru diputuskan</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Mahasiswa</th><th>Jenis</th><th>Isi</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        @foreach ($recent as $c)
                            <tr>
                                <td title="{{ $c->internship->student->name }}">{{ $c->internship->student->listName() }}</td>
                                <td>{{ $c->typeLabel() }}</td>
                                <td class="muted">{{ $c->summary() }}</td>
                                <td><x-chip :tone="$c->statusTone()">{{ $c->statusLabel() }}</x-chip></td>
                                <td class="muted">{{ $c->decided_at?->locale('id')->translatedFormat('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
