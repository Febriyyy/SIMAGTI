@extends('layouts.app')

@section('title', 'Validasi '.$internship->student->name)

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('validasi.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke validasi penilaian</a>
            <h1 class="page-title">Penilaian {{ $internship->student->name }}</h1>
            <p class="page-sub">
                Dinilai {{ $assessment?->assessor?->name ?? 'penilai' }}
                @if ($assessment?->submitted_at) · dikirim {{ $assessment->submitted_at->locale('id')->translatedFormat('j M Y, H:i') }} @endif
            </p>
        </div>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Nilai per aspek</h2>
                </div>
                @if ($assessment && $rubric)
                    @include('penilaian._scores')
                @else
                    <div class="empty">
                        <strong>Belum ada penilaian</strong>
                        Company Supervisor belum mengisi penilaian untuk mahasiswa ini.
                    </div>
                @endif
            </section>

            @if ($canReview)
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Keputusan</h2>
                            <p class="card-sub">Validasi mengunci nilai akhir dan huruf mutu secara permanen.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('validasi.update', $internship) }}" class="stack">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="catatan" class="label">Catatan</label>
                                <textarea id="catatan" name="catatan" class="textarea" maxlength="500" placeholder="Wajib diisi jika dikembalikan ke penilai">{{ old('catatan') }}</textarea>
                            </div>
                            <div class="btn-row">
                                <button type="submit" name="keputusan" value="kembalikan" class="btn">Kembalikan ke penilai</button>
                                <button type="submit" name="keputusan" value="validasi" class="btn btn-primary">Validasi dan kunci nilai</button>
                            </div>
                        </form>
                    </div>
                </section>
            @elseif ($assessment?->isValidated() && $assessment->supervisor_note)
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Catatan validasi</h2></div>
                    <div class="card-body"><p>{{ $assessment->supervisor_note }}</p></div>
                </section>
            @elseif ($assessment?->wasReturned())
                <div class="alert alert-error" role="status">Dikembalikan ke penilai: {{ $assessment->supervisor_note }}</div>
            @endif
        </div>

        <div class="col">
            @include('penilaian._summary')
            @include('penilaian._student')
        </div>
    </div>
@endsection
