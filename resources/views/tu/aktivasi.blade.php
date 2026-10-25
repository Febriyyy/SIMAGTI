@extends('layouts.app')

@section('title', 'Aktivasi Company Supervisor')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Aktivasi Company Supervisor</h1>
            <p class="page-sub">Satu supervisor cukup diaktifkan sekali.</p>
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

    @if ($disputed->isNotEmpty())
        <section class="card callout" id="disanggah">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Data penempatan dipertanyakan</h2>
                    <p class="card-sub">Dilaporkan Company Supervisor</p>
                </div>
                <x-chip tone="red">{{ $disputed->count() }}</x-chip>
            </div>
            <ul class="item-list">
                @foreach ($disputed as $internship)
                    <li class="item-row change">
                        <x-avatar :user="$internship->student" />
                        <div class="grow min0">
                            <div class="cell-title" title="{{ $internship->student->name }}">{{ $internship->student->listName() }}</div>
                            <div class="cell-sub">Supervisor {{ $internship->supervisor?->listName() ?? '–' }}</div>
                        </div>
                        <x-waiting :since="$internship->supervisor_confirmed_at" />
                        <x-detail>
                            <dl>
                                <div><dt>Laporan supervisor</dt><dd>{{ $internship->supervisor_confirmation_note }}</dd></div>
                                <div><dt>Mahasiswa</dt><dd>{{ $internship->student->name }} · {{ $internship->student->nim }}</dd></div>
                                <div><dt>Penempatan</dt><dd>{{ $internship->company?->name }}{{ $internship->division ? ' · Divisi '.$internship->division->name : '' }} · {{ $internship->position ?? '–' }} · {{ $internship->periodLabel() }}</dd></div>
                                <div><dt>Company Supervisor</dt><dd>{{ $internship->supervisor?->name ?? '–' }}</dd></div>
                                <div><dt>Langkah</dt><dd>Perbaiki bersama mahasiswa lalu minta konfirmasi ulang. Bila bukan pembimbingnya, lepas supervisor.</dd></div>
                            </dl>
                        </x-detail>
                        <div class="row-actions">
                            <form method="POST" action="{{ route('tu.aktivasi.lepas', $internship) }}" onsubmit="return confirm('Lepaskan supervisor ini dari penempatan? Penempatan kembali ke antrean aktivasi.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm">Lepas</button>
                            </form>
                            <form method="POST" action="{{ route('tu.aktivasi.ulang', $internship) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-primary">Minta ulang</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Menunggu aktivasi</h2>
                <p class="card-sub">Sudah disetujui kaprodi</p>
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
                                    <div class="cell-title" title="{{ $group['name'] }}">{{ $group['name'] ? \App\Models\User::shortenName($group['name']) : 'Nama belum diisi' }}</div>
                                    <div class="cell-sub">{{ $group['internships']->count() }} mahasiswa</div>
                                </div>
                            </div>
                            @if ($group['user'])
                                <x-chip :tone="$group['user']->role === 'supervisor' ? 'blue' : 'red'">{{ $group['user']->role === 'supervisor' ? 'Punya akun' : 'Email bentrok' }}</x-chip>
                            @elseif ($group['invitation'])
                                <x-chip tone="amber">Diundang</x-chip>
                            @else
                                <x-chip tone="gray">Belum diundang</x-chip>
                            @endif
                        </div>

                        <x-detail>
                            <dl>
                                <div><dt>Company Supervisor</dt><dd>{{ $group['name'] ?? '–' }}{{ $group['title'] ? ' · '.$group['title'] : '' }}</dd></div>
                                <div><dt>Perusahaan</dt><dd>{{ $group['company']?->name }}</dd></div>
                                <div><dt>Kontak</dt><dd>{{ $group['email'] }}@if ($group['phone']) · {{ $group['phone'] }}@endif</dd></div>
                                <div><dt>Mahasiswa</dt><dd>@foreach ($group['internships'] as $internship){{ $internship->student->name }} · {{ $internship->position ?? '–' }} · {{ $internship->periodLabel() }}@if (! $loop->last)<br>@endif @endforeach</dd></div>
                                @if ($group['user'] && $group['user']->role !== 'supervisor')
                                    <div><dt>Catatan</dt><dd>Email ini sudah dipakai akun dengan peran lain. Koreksi emailnya.</dd></div>
                                @endif
                                @if ($group['invitation'])
                                    <div><dt>Link undangan (s.d. {{ $group['invitation']->expires_at->locale('id')->translatedFormat('j M') }})</dt>
                                        <dd><input type="text" class="input input-sm" readonly value="{{ route('undangan.show', $group['invitation']->token) }}" onclick="this.select()" aria-label="Link undangan {{ $group['email'] }}"></dd></div>
                                @endif
                                <div><dt>Koreksi email</dt>
                                    <dd>
                                        <form method="POST" action="{{ route('tu.aktivasi.email') }}" class="inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="company_id" value="{{ $group['company']->id }}">
                                            <input type="hidden" name="email" value="{{ $group['email'] }}">
                                            <input type="email" name="new_email" class="input input-sm" placeholder="Email yang benar" required aria-label="Email baru">
                                            <button type="submit" class="btn btn-sm">Simpan</button>
                                        </form>
                                    </dd>
                                </div>
                            </dl>
                        </x-detail>

                        <div class="row-actions">
                            @if ($group['user'] && $group['user']->role === 'supervisor')
                                <form method="POST" action="{{ route('tu.aktivasi.tautkan') }}">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $group['company']->id }}">
                                    <input type="hidden" name="email" value="{{ $group['email'] }}">
                                    <button type="submit" class="btn btn-primary btn-sm"><x-ikon name="link" :size="16" /> Tautkan</button>
                                </form>
                            @elseif (! $group['user'])
                                <form method="POST" action="{{ route('tu.aktivasi.undang') }}">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $group['company']->id }}">
                                    <input type="hidden" name="email" value="{{ $group['email'] }}">
                                    <button type="submit" class="btn btn-primary btn-sm"><x-ikon name="send" :size="16" /> {{ $group['invitation'] ? 'Undang ulang' : 'Undang' }}</button>
                                </form>
                            @endif
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
                                <td class="cell-title" title="{{ $staff->user?->name }}">{{ $staff->user?->listName() }}</td>
                                <td title="{{ $staff->internship?->student?->name }}">{{ $staff->internship?->student?->listName() }}</td>
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
