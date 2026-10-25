@extends('layouts.app')

@section('title', 'Kuesioner Penilaian')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Kuesioner Penilaian</h1>
            <p class="page-sub">Diisi supervisor di akhir KP.</p>
        </div>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Kuesioner saya</h2>
                        <p class="card-sub">Kuesioner yang sudah dipakai menilai tidak bisa diubah, buat salinannya</p>
                    </div>
                </div>
                @if ($mine->isEmpty())
                    <div class="empty"><strong>Belum ada kuesioner</strong> Tanpa kuesioner sendiri, penilaian memakai rubrik kampus{{ $default ? ' "'.$default->name.'"' : '' }}.</div>
                @else
                    <ul class="item-list">
                        @foreach ($mine as $r)
                            <li class="item-row">
                                <span class="item-icon tone-bg-blue"><x-ikon name="list" :size="18" /></span>
                                <div class="grow min0">
                                    <div class="cell-title">{{ $r->name }}</div>
                                    <div class="cell-sub">{{ $r->aspects_count }} aspek · dipakai {{ $r->assessments_count }} penilaian</div>
                                </div>
                                <a href="{{ route('kuesioner.edit', $r) }}" class="btn btn-sm">{{ $r->assessments_count ? 'Lihat' : 'Ubah' }}</a>
                                <form method="POST" action="{{ route('kuesioner.store') }}">
                                    @csrf
                                    <input type="hidden" name="source_id" value="{{ $r->id }}">
                                    <input type="hidden" name="name" value="Salinan {{ \Illuminate\Support\Str::limit($r->name, 100, '') }}">
                                    <button type="submit" class="btn btn-sm btn-ghost">Salin</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="card-body">
                    <form method="POST" action="{{ route('kuesioner.store') }}" class="inline-form">
                        @csrf
                        <input type="text" name="name" class="input" maxlength="120" required placeholder="Nama kuesioner baru" aria-label="Nama kuesioner baru">
                        <button type="submit" class="btn btn-primary"><x-ikon name="plus" :size="18" /> Buat dari rubrik kampus</button>
                    </form>
                </div>
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Pakai untuk mahasiswa</h2>
                        <p class="card-sub">Bisa diganti selama penilaian akhir belum dimulai</p>
                    </div>
                </div>
                @if ($students->isEmpty())
                    <p class="meeting-empty">Belum ada mahasiswa bimbingan yang KP-nya berjalan.</p>
                @else
                    <form method="POST" action="{{ route('kuesioner.assign') }}">
                        @csrf
                        @method('PATCH')
                        <ul class="item-list">
                            @foreach ($students as $i)
                                <li class="item-row">
                                    <x-avatar :user="$i->student" />
                                    <div class="grow min0">
                                        <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                                        <div class="cell-sub">{{ $i->programTypeLabel() }}{{ $i->assessment ? ' · penilaian sudah dimulai' : '' }}</div>
                                    </div>
                                    <select name="assign[{{ $i->id }}]" class="input input-sm" @disabled($i->assessment) aria-label="Kuesioner untuk {{ $i->student->name }}">
                                        <option value="">Rubrik kampus</option>
                                        @foreach ($mine as $r)
                                            <option value="{{ $r->id }}" @selected((int) $i->assessment_rubric_id === $r->id)>{{ $r->name }}</option>
                                        @endforeach
                                    </select>
                                </li>
                            @endforeach
                        </ul>
                        <div class="card-body"><button type="submit" class="btn btn-primary">Simpan pilihan</button></div>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection
