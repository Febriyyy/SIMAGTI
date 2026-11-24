@extends('layouts.app')

@section('title', 'Validasi '.$internship->student->name)

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('validasi.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke validasi penilaian</a>
            <h1 class="page-title">Penilaian {{ $internship->student->name }}</h1>
            <p class="page-sub">Dosen pembimbing dan Company Supervisor, masing-masing 50%.</p>
        </div>
    </div>

    <div class="layout">
        <div class="col">
            {{-- A. Penilaian dosen pembimbing: kartu sendiri, aksen lime --}}
            <section class="card score-card is-lecturer">
                <div class="card-head score-card-head">
                    <span class="score-card-mark" aria-hidden="true">A</span>
                    <div class="min0">
                        <h2 class="card-title">Nilai dosen pembimbing</h2>
                        <p class="card-sub">Laporan akhir · 50% nilai sidang</p>
                    </div>
                    @if ($internship->lecturer)
                        <span class="score-card-by">
                            <x-avatar :user="$internship->lecturer" small />
                            <span><b>{{ $internship->lecturer->listName() }}</b><small>Dosen pembimbing</small></span>
                        </span>
                    @endif
                </div>
                @include('penilaian._report-scores', ['report' => $internship->reportScore, 'canGrade' => (int) $internship->lecturer_id === (int) auth()->id()])
            </section>

            {{-- B. Penilaian Company Supervisor: kartu sendiri, aksen hijau; yang divalidasi di halaman ini --}}
            <section class="card score-card is-supervisor">
                <div class="card-head score-card-head">
                    <span class="score-card-mark" aria-hidden="true">B</span>
                    <div class="min0">
                        <h2 class="card-title">Nilai Company Supervisor</h2>
                        <p class="card-sub">Hard skill, soft skill, attitude · 50% nilai sidang{{ $assessment?->submitted_at ? ' · dikirim '.$assessment->submitted_at->locale('id')->translatedFormat('j M Y') : '' }}</p>
                    </div>
                    @if ($assessment?->assessor)
                        <span class="score-card-by">
                            <x-avatar :user="$assessment->assessor" small />
                            <span><b>{{ $assessment->assessor->listName() }}</b><small>Company Supervisor</small></span>
                        </span>
                    @endif
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
                            <p class="card-sub">Nilai terkunci permanen setelah validasi.</p>
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
                            <div class="row-actions">
                                <button type="submit" name="keputusan" value="kembalikan" class="btn">Kembalikan</button>
                                <button type="submit" name="keputusan" value="validasi" class="btn btn-primary">Validasi</button>
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
