@extends('layouts.app')

@section('title', 'Aktivasi Company Supervisor')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Aktivasi Company Supervisor</h1>
            <p class="page-sub">Dari data yang diisi mahasiswa saat pengajuan. Satu supervisor cukup diaktifkan sekali untuk semua mahasiswanya.</p>
        </div>
    </div>

    @if (session('invitation_link'))
        <section class="card callout">
            <div class="card-body pt">
                <label for="invite-link" class="label">Link aktivasi (berlaku 14 hari)</label>
                <div class="inline-form">
                    <input id="invite-link" type="text" class="input" readonly value="{{ session('invitation_link') }}" onclick="this.select()">
                    <button type="button" class="btn" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('invite-link').value); this.textContent='Tersalin'">Salin</button>
                </div>
                <p class="hint">Pengiriman lewat email otomatis akan aktif setelah server email (SMTP) disiapkan.</p>
            </div>
        </section>
    @endif

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Menunggu aktivasi</h2>
                <p class="card-sub">Pengajuan magang yang sudah disetujui kaprodi, belum punya Company Supervisor</p>
            </div>
            <x-chip :tone="$groups->isEmpty() ? 'green' : 'amber'">{{ $groups->sum(fn ($g) => $g['internships']->count()) }} mahasiswa</x-chip>
        </div>

        @if ($groups->isEmpty())
            <div class="empty">
                <strong>Tidak ada antrean</strong>
                Semua mahasiswa magang sudah punya Company Supervisor.
            </div>
        @else
            <ul class="activation-list">
                @foreach ($groups as $group)
                    <li class="activation">
                        <div class="activation-head">
                            <div class="activation-who">
                                <span class="activation-icon"><x-ikon name="person" :size="20" /></span>
                                <div class="min0">
                                    <div class="cell-title">{{ $group['name'] ?? 'Nama belum diisi' }}</div>
                                    <div class="cell-sub">{{ $group['title'] ?? 'Jabatan belum diisi' }} · {{ $group['company']?->name }}</div>
                                    <div class="cell-sub"><x-ikon name="mail" :size="13" /> {{ $group['email'] }}@if ($group['phone']) · {{ $group['phone'] }}@endif</div>
                                </div>
                            </div>
                            <div class="activation-status">
                                @if ($group['user'])
                                    <x-chip :tone="$group['user']->role === 'supervisor' ? 'blue' : 'red'">{{ $group['user']->role === 'supervisor' ? 'Sudah punya akun' : 'Email dipakai peran lain' }}</x-chip>
                                @elseif ($group['invitation'])
                                    <x-chip tone="amber">Undangan terkirim · s.d. {{ $group['invitation']->expires_at->locale('id')->translatedFormat('j M') }}</x-chip>
                                @else
                                    <x-chip tone="gray">Belum diundang</x-chip>
                                @endif
                            </div>
                        </div>

                        <div class="activation-students">
                            @foreach ($group['internships'] as $internship)
                                <div class="activation-student">
                                    <x-person :user="$internship->student" />
                                    <span class="cell-sub">{{ $internship->division?->name ? 'Divisi '.$internship->division->name.' · ' : '' }}{{ $internship->position ?? '–' }} · {{ $internship->periodLabel() }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="activation-actions">
                            @if ($group['user'] && $group['user']->role === 'supervisor')
                                <form method="POST" action="{{ route('tu.aktivasi.tautkan') }}">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $group['company']->id }}">
                                    <input type="hidden" name="email" value="{{ $group['email'] }}">
                                    <button type="submit" class="btn btn-primary btn-sm"><x-ikon name="link" :size="16" /> Tautkan ke akun {{ $group['user']->shortName() }}</button>
                                </form>
                            @elseif (! $group['user'])
                                <form method="POST" action="{{ route('tu.aktivasi.undang') }}">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $group['company']->id }}">
                                    <input type="hidden" name="email" value="{{ $group['email'] }}">
                                    <button type="submit" class="btn btn-primary btn-sm"><x-ikon name="send" :size="16" /> {{ $group['invitation'] ? 'Terbitkan ulang undangan' : 'Terbitkan undangan' }}</button>
                                </form>
                                @if ($group['invitation'])
                                    <input type="text" class="input input-sm grow" readonly value="{{ route('undangan.show', $group['invitation']->token) }}" onclick="this.select()" aria-label="Link undangan {{ $group['email'] }}">
                                @endif
                            @endif

                            <details class="inline-details">
                                <summary class="btn btn-sm">Koreksi email</summary>
                                <form method="POST" action="{{ route('tu.aktivasi.email') }}" class="inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="company_id" value="{{ $group['company']->id }}">
                                    <input type="hidden" name="email" value="{{ $group['email'] }}">
                                    <input type="email" name="new_email" class="input input-sm" placeholder="Email yang benar" required aria-label="Email baru">
                                    <button type="submit" class="btn btn-sm">Simpan</button>
                                </form>
                            </details>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($recent->isNotEmpty())
        <section class="card">
            <div class="card-head">
                <h2 class="card-title">Baru diaktifkan</h2>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Company Supervisor</th><th>Mahasiswa</th><th>Perusahaan</th><th>Sejak</th></tr></thead>
                    <tbody>
                        @foreach ($recent as $staff)
                            <tr>
                                <td class="cell-title">{{ $staff->user?->name }}</td>
                                <td>{{ $staff->internship?->student?->name }}</td>
                                <td>{{ $staff->internship?->company?->name }}</td>
                                <td class="muted">{{ $staff->created_at->locale('id')->translatedFormat('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
