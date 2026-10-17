@extends('layouts.app')

@section('title', 'Persetujuan KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Persetujuan KP Mandiri</h1>
            <p class="page-sub">Pengajuan yang perusahaannya sudah diverifikasi Biro Kerjasama.</p>
        </div>
    </div>

    @if ($waiting->isEmpty())
        <div class="card">
            <div class="empty">
                <strong>Tidak ada pengajuan yang menunggu</strong>
                Pengajuan akan muncul setelah diverifikasi Biro Kerjasama.
            </div>
        </div>
    @else
        @foreach ($waiting as $item)
            @php($biro = $item->approvals->firstWhere('step', \App\Models\InternshipApproval::STEP_BIRO))
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">{{ $item->student->name }} ({{ $item->student->nim }})</h2>
                        <p class="card-sub">{{ $item->company->name }}@if ($item->division) · {{ $item->division->name }}@endif</p>
                    </div>
                    <x-chip tone="green">Terverifikasi biro</x-chip>
                </div>

                <div class="card-body">
                    <dl class="kv">
                        <div><dt>Bidang</dt><dd>{{ $item->company->industry ?? '–' }}</dd></div>
                        <div><dt>Alamat</dt><dd>{{ $item->company->address ?? '–' }}@if ($item->company->city), {{ $item->company->city }}@endif</dd></div>
                        <div><dt>Periode</dt><dd>{{ $item->start_date->locale('id')->translatedFormat('j F Y') }} – {{ $item->end_date->locale('id')->translatedFormat('j F Y') }}</dd></div>
                        <div><dt>Diverifikasi</dt><dd>{{ $biro?->reviewer?->name ?? '–' }}@if ($biro?->reviewed_at), {{ $biro->reviewed_at->locale('id')->translatedFormat('j F Y') }}@endif</dd></div>
                        <div>
                        <dt>Undangan</dt>
                        <dd>
                            @forelse ($item->invitations as $invitation)
                                {{ $invitation->email }} ({{ \App\Models\User::ROLE_LABELS[$invitation->role] ?? $invitation->role }})@if (! $loop->last), @endif
                            @empty
                                –
                            @endforelse
                        </dd>
                        </div>
                    </dl>

                    <div class="toolbar">
                        <form method="POST" action="{{ route('kaprodi.persetujuan.update', $item) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="approve">
                            <select name="lecturer_id" class="select select-sm" required aria-label="Dosen pembimbing untuk {{ $item->student->name }}">
                                <option value="">Pilih dosen pembimbing</option>
                                @foreach ($lecturers as $lecturer)
                                    <option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Setujui</button>
                        </form>

                        <form method="POST" action="{{ route('kaprodi.persetujuan.update', $item) }}" class="inline-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="reject">
                            <input type="text" name="note" class="input" placeholder="Alasan penolakan" maxlength="500" required aria-label="Alasan penolakan untuk {{ $item->student->name }}">
                            <button type="submit" class="btn btn-sm">Tolak</button>
                        </form>
                    </div>
                </div>
            </section>
        @endforeach
    @endif
@endsection
