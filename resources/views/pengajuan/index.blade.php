@extends('layouts.app')

@section('title', 'Pengajuan KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Pengajuan KP</h1>
            <p class="page-sub">Ajukan tempat KP yang Anda cari sendiri. Prosesnya: verifikasi Biro Kerjasama, lalu persetujuan Kaprodi.</p>
        </div>
    </div>

    @if ($internship)
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">{{ $internship->company->name }}</h2>
                    <p class="card-sub">
                        {{ $internship->start_date->locale('id')->translatedFormat('j F Y') }}
                        – {{ $internship->end_date->locale('id')->translatedFormat('j F Y') }}
                    </p>
                </div>
                <x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Tahap</th><th>Status</th><th>Catatan</th></tr>
                    </thead>
                    <tbody>
                        @foreach ([\App\Models\InternshipApproval::STEP_BIRO => 'Verifikasi Biro Kerjasama', \App\Models\InternshipApproval::STEP_KAPRODI => 'Persetujuan Kaprodi'] as $step => $label)
                            @php($approval = $internship->approvals->firstWhere('step', $step))
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    @if ($approval)
                                        <x-chip :tone="\App\Models\InternshipApproval::STATUSES[$approval->status][1]">{{ \App\Models\InternshipApproval::STATUSES[$approval->status][0] }}</x-chip>
                                    @else
                                        <span class="muted">–</span>
                                    @endif
                                </td>
                                <td>{{ $approval?->note ?? '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if ($internship->invitations->isNotEmpty())
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Undangan mentor dan ketua divisi</h2>
                        <p class="card-sub">Jika email belum sampai, salin link di bawah dan kirim sendiri lewat WhatsApp atau email.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Email</th><th>Peran</th><th>Status</th><th>Link aktivasi</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($internship->invitations as $invitation)
                                <tr>
                                    <td>{{ $invitation->email }}</td>
                                    <td>{{ \App\Models\User::ROLE_LABELS[$invitation->role] ?? $invitation->role }}</td>
                                    <td>
                                        @if ($invitation->isAccepted())
                                            <x-chip tone="green">Sudah aktif</x-chip>
                                        @elseif ($invitation->isExpired())
                                            <x-chip tone="red">Kedaluwarsa</x-chip>
                                        @else
                                            <x-chip tone="amber">Menunggu</x-chip>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invitation->isUsable())
                                            <input type="text" class="input" readonly value="{{ route('undangan.show', $invitation->token) }}" onclick="this.select()" aria-label="Link aktivasi untuk {{ $invitation->email }}">
                                        @else
                                            <span class="muted">–</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif

    @if ($canSubmit)
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">{{ $internship ? 'Ajukan ulang' : 'Ajukan KP mandiri' }}</h2>
                    <p class="card-sub">Isi data perusahaan tempat Anda akan magang.</p>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('pengajuan.store') }}" class="form-grid">
                    @csrf

                    <div>
                        <label for="company_name" class="label">Nama perusahaan</label>
                        <input id="company_name" name="company_name" type="text" class="input" value="{{ old('company_name') }}" maxlength="150" required>
                    </div>
                    <div>
                        <label for="industry" class="label">Bidang usaha</label>
                        <input id="industry" name="industry" type="text" class="input" value="{{ old('industry') }}" maxlength="100">
                    </div>
                    <div>
                        <label for="city" class="label">Kota</label>
                        <input id="city" name="city" type="text" class="input" value="{{ old('city') }}" maxlength="100">
                    </div>
                    <div>
                        <label for="division_name" class="label">Divisi penempatan</label>
                        <input id="division_name" name="division_name" type="text" class="input" value="{{ old('division_name') }}" maxlength="100">
                    </div>
                    <div class="span-all">
                        <label for="address" class="label">Alamat</label>
                        <textarea id="address" name="address" class="textarea" maxlength="500">{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label for="company_email" class="label">Email perusahaan</label>
                        <input id="company_email" name="company_email" type="email" class="input" value="{{ old('company_email') }}" maxlength="150">
                    </div>
                    <div>
                        <label for="phone" class="label">Telepon</label>
                        <input id="phone" name="phone" type="text" class="input" value="{{ old('phone') }}" maxlength="20">
                    </div>
                    <div class="span-all">
                        <label for="website" class="label">Website</label>
                        <input id="website" name="website" type="url" class="input" value="{{ old('website') }}" maxlength="200" placeholder="https://">
                    </div>
                    <div>
                        <label for="start_date" class="label">Tanggal mulai</label>
                        <input id="start_date" name="start_date" type="date" class="input" value="{{ old('start_date') }}" required>
                    </div>
                    <div>
                        <label for="end_date" class="label">Tanggal selesai</label>
                        <input id="end_date" name="end_date" type="date" class="input" value="{{ old('end_date') }}" required>
                    </div>
                    <div>
                        <label for="mentor_email" class="label">Email mentor (opsional)</label>
                        <input id="mentor_email" name="mentor_email" type="email" class="input" value="{{ old('mentor_email') }}" maxlength="150">
                        <p class="hint">Mentor akan mendapat link untuk mengaktifkan akun.</p>
                    </div>
                    <div>
                        <label for="supervisor_email" class="label">Email ketua divisi (opsional)</label>
                        <input id="supervisor_email" name="supervisor_email" type="email" class="input" value="{{ old('supervisor_email') }}" maxlength="150">
                    </div>
                    <div class="span-all">
                        <button type="submit" class="btn btn-primary">Kirim pengajuan</button>
                    </div>
                </form>
            </div>
        </section>
    @endif
@endsection
