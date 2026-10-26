@extends('layouts.app')

@section('title', 'Status KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Status KP</h1>
            <p class="page-sub">Kemajuan KP Anda, langkah demi langkah.</p>
        </div>
    </div>

    @if ($internship)
        @php($current = \App\Support\KpProgress::current($internship, $steps))
        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Kemajuan KP</h2>
                            <p class="card-sub">{{ collect($steps)->where('state', 'done')->count() }} dari {{ count($steps) }} langkah selesai</p>
                        </div>
                        <x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip>
                    </div>

                    @if ($current && $internship->status !== 'ditolak')
                        <div class="next-step next-{{ $current['state'] }}">
                            <x-ikon :name="$current['state'] === 'attention' ? 'alert' : ($current['state'] === 'waiting' ? 'clock' : 'arrow')" :size="20" />
                            <div>
                                <div class="next-step-title">
                                    {{ $current['state'] === 'current' ? 'Giliran Anda' : ($current['state'] === 'attention' ? 'Perlu tindakan' : 'Menunggu '.$current['actor']) }}:
                                    {{ $current['title'] }}
                                </div>
                                <div class="next-step-sub">{{ $current['detail'] }}</div>
                            </div>
                            @if ($current['url'] && $current['url'] !== url()->current())
                                <a href="{{ $current['url'] }}" class="btn btn-sm btn-primary">{{ $current['cta'] ?? 'Buka' }}</a>
                            @endif
                        </div>
                    @endif

                    <ol class="steps">
                        @foreach ($steps as $i => $step)
                            <li @class(['step', 'is-'.$step['state'], 'has-link' => $step['url']])>
                                <span class="step-dot" aria-hidden="true">
                                    @if ($step['state'] === 'done')
                                        <x-ikon name="check" :size="16" fill />
                                    @elseif ($step['state'] === 'attention')
                                        <x-ikon name="alert" :size="15" />
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                                <div class="step-body">
                                    <div class="step-head">
                                        @if ($step['url'] && $step['state'] !== 'upcoming')
                                            <a href="{{ $step['url'] }}" class="step-title step-link">{{ $step['title'] }} <x-ikon name="arrow" :size="14" /></a>
                                        @else
                                            <span class="step-title">{{ $step['title'] }}</span>
                                        @endif
                                        <span class="step-state">{{ ['done' => 'Selesai', 'current' => 'Berjalan', 'waiting' => 'Menunggu', 'attention' => ['supervisor' => 'Perlu dicek', 'result' => 'Tidak diterima', 'documents' => 'Perlu diunggah'][$step['key']] ?? 'Ditolak', 'upcoming' => 'Belum', 'later' => 'Segera hadir'][$step['state']] }}</span>
                                    </div>
                                    <div class="step-meta">
                                        {{ $step['actor'] }}
                                        @if ($step['date'])
                                            · {{ $step['date']->locale('id')->translatedFormat('j M Y') }}
                                        @endif
                                    </div>
                                    @if ($step['detail'])
                                        <p class="step-detail">{{ $step['detail'] }}</p>
                                    @endif
                                    @if ($step['note'])
                                        <p class="step-note"><strong>Catatan:</strong> {{ $step['note'] }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>

            <div class="col">
                <section class="card">
                    <div class="card-head">
                        <h2 class="card-title">Ringkasan</h2>
                    </div>
                    <div class="card-body">
                        <dl class="kv">
                            <div><dt>Perusahaan</dt><dd>{{ $internship->company?->name }}@if ($internship->company?->city)<small>{{ $internship->company->city }}</small>@endif</dd></div>
                            <div><dt>Divisi</dt><dd>{{ $internship->division?->name ?? '–' }}</dd></div>
                            <div><dt>Posisi</dt><dd>{{ $internship->position ?? '–' }}</dd></div>
                            <div><dt>Company Supervisor</dt><dd>{{ $internship->supervisor?->listName() ?? $internship->supervisor_name ?? '–' }}@if ($internship->supervisor_title)<small>{{ $internship->supervisor_title }}</small>@endif</dd></div>
                            <div><dt>Dosen pembimbing</dt><dd>{{ $internship->lecturer?->listName() ?? 'Belum ditunjuk' }}</dd></div>
                            <div><dt>Periode</dt><dd>{{ $internship->periodLabel() }}<small>{{ $internship->durationMonths() }} bulan</small></dd></div>
                            @if ($internship->result)
                                <div><dt>Hasil KP</dt><dd><x-chip :tone="$internship->resultTone()">{{ $internship->resultLabel() }}</x-chip>@if ($internship->result_note)<small>{{ $internship->result_note }}</small>@endif</dd></div>
                            @endif
                        </dl>
                    </div>
                </section>
            </div>
        </div>
    @endif

    @if ($canSubmit)
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">{{ $internship ? 'Ajukan ulang' : 'Ajukan KP' }}</h2>
                    <p class="card-sub">
                        Untuk mahasiswa yang sudah mendapat tempat KP sendiri.
                        @if ($semester)
                            Saat ini Anda semester {{ $semester }}.
                        @endif
                    </p>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('kp.apply') }}" class="stack">
                    @csrf

                    <div class="form-section">
                        <h3 class="form-section-title">Perusahaan</h3>
                        <div class="form-grid">
                            <div>
                                <label for="company_name" class="label">Nama perusahaan</label>
                                <input id="company_name" name="company_name" type="text" class="input" value="{{ old('company_name') }}" maxlength="150" required>
                            </div>
                            <x-industry-select required />
                            <div>
                                <label for="division_name" class="label">Divisi penempatan</label>
                                <input id="division_name" name="division_name" type="text" class="input" value="{{ old('division_name') }}" maxlength="100" placeholder="Misalnya Engineering">
                            </div>
                            <div>
                                <label for="position" class="label">Posisi magang</label>
                                <input id="position" name="position" type="text" class="input" value="{{ old('position') }}" maxlength="100" placeholder="Misalnya Frontend Developer Intern" required>
                                <p class="hint">Bisa dikoreksi Company Supervisor nanti.</p>
                            </div>
                            <div>
                                <label for="city" class="label">Kota</label>
                                <input id="city" name="city" type="text" class="input" value="{{ old('city') }}" maxlength="100">
                            </div>
                            <div>
                                <label for="company_email" class="label">Email perusahaan</label>
                                <input id="company_email" name="company_email" type="email" class="input" value="{{ old('company_email') }}" maxlength="150">
                            </div>
                            <div class="span-all">
                                <label for="address" class="label">Alamat</label>
                                <textarea id="address" name="address" class="textarea" maxlength="500" rows="2">{{ old('address') }}</textarea>
                            </div>
                            <div>
                                <label for="phone" class="label">Telepon perusahaan</label>
                                <input id="phone" name="phone" type="text" class="input" value="{{ old('phone') }}" maxlength="20">
                            </div>
                            <div>
                                <label for="website" class="label">Website <span class="label-opt">(opsional)</span></label>
                                <input id="website" name="website" type="text" inputmode="url" class="input" value="{{ old('website') }}" maxlength="200" placeholder="perusahaan.co.id">
                                <p class="hint">Kosongkan bila perusahaan tidak punya website.</p>
                            </div>
                        </div>

                        <h3 class="form-section-title">Company Supervisor</h3>
                        <p class="hint" style="margin-top: calc(var(--space-2) * -1)">Pembimbing Anda di perusahaan. Setelah pengajuan disetujui, TU Fakultas mengaktifkan akunnya.</p>
                        <div class="form-grid">
                            <div>
                                <label for="supervisor_name" class="label">Nama</label>
                                <input id="supervisor_name" name="supervisor_name" type="text" class="input" value="{{ old('supervisor_name') }}" maxlength="150" required>
                            </div>
                            <div>
                                <label for="supervisor_title" class="label">Jabatan</label>
                                <input id="supervisor_title" name="supervisor_title" type="text" class="input" value="{{ old('supervisor_title') }}" maxlength="100" placeholder="Misalnya Engineering Manager">
                            </div>
                            <div>
                                <label for="supervisor_email" class="label">Email</label>
                                <input id="supervisor_email" name="supervisor_email" type="email" class="input" value="{{ old('supervisor_email') }}" maxlength="150" required>
                            </div>
                            <div>
                                <label for="supervisor_phone" class="label">Nomor HP</label>
                                <input id="supervisor_phone" name="supervisor_phone" type="text" class="input" value="{{ old('supervisor_phone') }}" maxlength="30">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Periode</h3>
                        <div class="form-grid">
                            <div>
                                <label for="start_date" class="label">Tanggal mulai</label>
                                <input id="start_date" name="start_date" type="date" class="input" value="{{ old('start_date') }}" required>
                            </div>
                            <div>
                                <label for="end_date" class="label">Tanggal selesai</label>
                                <input id="end_date" name="end_date" type="date" class="input" value="{{ old('end_date') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row-actions"><button type="submit" class="btn btn-primary"><x-ikon name="send" :size="18" /> Kirim pengajuan</button></div>
                </form>
            </div>
        </section>
    @endif
@endsection
