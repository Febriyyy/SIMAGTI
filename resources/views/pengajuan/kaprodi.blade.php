@extends('layouts.app')

@section('title', 'Persetujuan KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Persetujuan KP</h1>
            <p class="page-sub">Sudah diverifikasi biro, atau seminar.</p>
        </div>
    </div>

    @if ($waiting->isEmpty())
        <div class="card">
            <div class="empty">
                <strong>Tidak ada pengajuan yang menunggu</strong>
                Pengajuan magang muncul setelah diverifikasi Biro Kerjasama; seminar langsung muncul di sini.
            </div>
        </div>
    @else
        @foreach ($waiting as $item)
            @php($biro = $item->approvals->firstWhere('step', \App\Models\InternshipApproval::STEP_BIRO))
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title" title="{{ $item->student->name }}">{{ $item->student->listName() }}</h2>
                        <p class="card-sub">{{ $item->student->nim }} · {{ $item->programTypeLabel() }}</p>
                    </div>
                    @if ($item->isSeminar())
                        <x-chip tone="purple">Seminar</x-chip>
                    @else
                        <x-chip tone="green">Terverifikasi biro</x-chip>
                    @endif
                </div>

                <div class="card-body">
                    <dl class="kv">
                        <div><dt>{{ $item->isSeminar() ? 'Kegiatan' : 'Tempat' }}</dt><dd>{{ $item->isSeminar() ? \Illuminate\Support\Str::limit($item->topic, 40) : $item->placeName() }}</dd></div>
                        <div><dt>Periode</dt><dd>{{ $item->periodLabel() }}</dd></div>
                    </dl>
                    <x-detail>
                        <dl>
                            <div><dt>Mahasiswa</dt><dd>{{ $item->student->name }} · {{ $item->student->nim }} · <x-academic :user="$item->student" compact /></dd></div>
                            @if ($item->isSeminar())
                                <div><dt>Kegiatan</dt><dd>{{ $item->topic }}</dd></div>
                                <div><dt>Penyelenggara</dt><dd>{{ $item->organizer ?? '–' }}</dd></div>
                            @else
                                <div><dt>Penempatan</dt><dd>{{ $item->placeName() }}@if ($item->placeDetail()) · {{ $item->placeDetail() }}@endif</dd></div>
                                <div><dt>Bidang</dt><dd>{{ $item->company?->industry ?? '–' }}</dd></div>
                                <div><dt>Alamat</dt><dd>{{ $item->company?->address ?? '–' }}@if ($item->company?->city), {{ $item->company->city }}@endif</dd></div>
                                <div><dt>Company Supervisor</dt><dd>{{ $item->supervisor_name ?? '–' }}{{ $item->supervisor_title ? ' · '.$item->supervisor_title : '' }}{{ $item->supervisor_email ? ' · '.$item->supervisor_email : '' }}</dd></div>
                                <div><dt>Diverifikasi</dt><dd>{{ $biro?->reviewer?->name ?? '–' }}@if ($biro?->reviewed_at), {{ $biro->reviewed_at->locale('id')->translatedFormat('j F Y') }}@endif</dd></div>
                            @endif
                        </dl>
                    </x-detail>

                    <div class="decision">
                        <div class="decision-col">
                            <p class="decision-label">Setujui dan tetapkan dosen pembimbing</p>
                            <form method="POST" action="{{ route('kaprodi.persetujuan.update', $item) }}" class="inline-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="approve">
                                <select name="lecturer_id" class="select" required aria-label="Dosen pembimbing untuk {{ $item->student->name }}">
                                    <option value="">Pilih dosen pembimbing</option>
                                    @foreach ($lecturers as $lecturer)
                                        <option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary">Setujui</button>
                            </form>
                        </div>

                        <div class="decision-col">
                            <p class="decision-label">Tolak dengan alasan</p>
                            <form method="POST" action="{{ route('kaprodi.persetujuan.update', $item) }}" class="inline-form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="reject">
                                <input type="text" name="note" class="input" placeholder="Alasan penolakan" maxlength="500" required aria-label="Alasan penolakan untuk {{ $item->student->name }}">
                                <button type="submit" class="btn">Tolak</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
@endsection
