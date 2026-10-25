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
        @php($current = collect($steps)->first(fn ($s) => in_array($s['state'], ['current', 'waiting', 'attention'], true)))
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
                            @if ($current['key'] === 'syllabus')
                                <a href="{{ route('silabus.index') }}" class="btn btn-sm btn-primary">Buka silabus</a>
                            @elseif ($current['key'] === 'activities')
                                <a href="{{ route('kegiatan.index') }}" class="btn btn-sm btn-primary">Buka kegiatan</a>
                            @endif
                        </div>
                    @endif

                    <ol class="steps">
                        @foreach ($steps as $i => $step)
                            <li @class(['step', 'is-'.$step['state']])>
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
                                        <span class="step-title">{{ $step['title'] }}</span>
                                        <span class="step-state">{{ ['done' => 'Selesai', 'current' => 'Berjalan', 'waiting' => 'Menunggu', 'attention' => ['syllabus' => 'Perlu revisi', 'supervisor' => 'Perlu dicek', 'result' => 'Tidak diterima'][$step['key']] ?? 'Ditolak', 'upcoming' => 'Belum', 'later' => 'Segera hadir'][$step['state']] }}</span>
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
                            <div><dt>Jalur</dt><dd>{{ $internship->programLabel() }}<small>{{ $internship->programTypeLabel() }}</small></dd></div>
                            @if ($internship->isSeminar())
                                <div><dt>Kegiatan</dt><dd>{{ $internship->topic }}@if ($internship->organizer)<small>{{ $internship->organizer }}</small>@endif</dd></div>
                            @else
                                <div><dt>Perusahaan</dt><dd>{{ $internship->company?->name }}@if ($internship->company?->city)<small>{{ $internship->company->city }}</small>@endif</dd></div>
                                <div><dt>Divisi</dt><dd>{{ $internship->division?->name ?? '–' }}</dd></div>
                                <div><dt>Posisi</dt><dd>{{ $internship->position ?? '–' }}</dd></div>
                                <div><dt>Company Supervisor</dt><dd>{{ $internship->supervisor?->listName() ?? $internship->supervisor_name ?? '–' }}@if ($internship->supervisor_title)<small>{{ $internship->supervisor_title }}</small>@endif</dd></div>
                            @endif
                            <div><dt>Dosen pembimbing</dt><dd>{{ $internship->lecturer?->listName() ?? 'Belum ditunjuk' }}</dd></div>
                            <div><dt>Periode</dt><dd>{{ $internship->periodLabel() }}<small>{{ $internship->durationMonths() }} bulan</small></dd></div>
                            @if ($internship->result)
                                <div><dt>Hasil KP</dt><dd><x-chip :tone="$internship->resultTone()">{{ $internship->resultLabel() }}</x-chip>@if ($internship->result_note)<small>{{ $internship->result_note }}</small>@endif</dd></div>
                            @endif
                        </dl>
                    </div>
                </section>

                @if ($internship->isMbkm() && in_array($internship->status, ['berjalan', 'selesai'], true))
                    @include('pengajuan._rekognisi', ['internship' => $internship, 'recognition' => $internship->creditRecognition])
                @endif
            </div>
        </div>
    @endif

    @if ($canSubmit)
        @php($program = old('program', 'magang'))
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">{{ $internship ? 'Ajukan ulang' : 'Ajukan KP' }}</h2>
                    <p class="card-sub">
                        Pilih jalur KP Anda.
                        @if ($semester)
                            Saat ini Anda semester {{ $semester }}.
                        @endif
                    </p>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('kp.apply') }}" class="stack" data-program-form data-program="{{ $program }}">
                    @csrf

                    <fieldset class="choice-group">
                        <legend class="label">Jalur dan jenis KP</legend>
                        <div class="choice-grid">
                            @foreach (\App\Models\Internship::PROGRAMS as $key => [$label, $types])
                                @foreach ($types as $type => $typeLabel)
                                    <label class="choice">
                                        <input type="radio" name="program_choice" value="{{ $key }}:{{ $type }}"
                                               @checked(old('program', 'magang') === $key && old('program_type', 'industri') === $type)
                                               onchange="this.form.program.value='{{ $key }}'; this.form.program_type.value='{{ $type }}'; this.form.dataset.program='{{ $key }}';">
                                        <span class="choice-body">
                                            <span class="choice-kicker">{{ $label }}</span>
                                            <span class="choice-title">{{ $typeLabel }}</span>
                                            <span class="choice-sub">{{ [
                                                'industri' => 'Magang di perusahaan, dinilai Company Supervisor',
                                                'mbkm' => 'Magang dengan rekognisi SKS, logbook minimal 450 jam',
                                                'pelaksana' => 'Menyelenggarakan kegiatan seminar, dipantau dosen',
                                                'peserta' => 'Mengikuti rangkaian seminar, dipantau dosen',
                                            ][$type] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                        <input type="hidden" name="program" value="{{ old('program', 'magang') }}">
                        <input type="hidden" name="program_type" value="{{ old('program_type', 'industri') }}">
                    </fieldset>

                    <div class="form-section" data-when="magang">
                        <h3 class="form-section-title">Perusahaan</h3>
                        <div class="form-grid">
                            <div>
                                <label for="company_name" class="label">Nama perusahaan</label>
                                <input id="company_name" name="company_name" type="text" class="input" value="{{ old('company_name') }}" maxlength="150">
                            </div>
                            <div>
                                <label for="industry" class="label">Bidang usaha</label>
                                <input id="industry" name="industry" type="text" class="input" value="{{ old('industry') }}" maxlength="100">
                            </div>
                            <div>
                                <label for="division_name" class="label">Divisi penempatan</label>
                                <input id="division_name" name="division_name" type="text" class="input" value="{{ old('division_name') }}" maxlength="100" placeholder="Misalnya Engineering">
                            </div>
                            <div>
                                <label for="position" class="label">Posisi magang</label>
                                <input id="position" name="position" type="text" class="input" value="{{ old('position') }}" maxlength="100" placeholder="Misalnya Frontend Developer Intern">
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
                                <label for="website" class="label">Website</label>
                                <input id="website" name="website" type="url" class="input" value="{{ old('website') }}" maxlength="200" placeholder="https://">
                            </div>
                        </div>

                        <h3 class="form-section-title">Company Supervisor</h3>
                        <p class="hint" style="margin-top: calc(var(--space-2) * -1)">Pembimbing Anda di perusahaan. Setelah pengajuan disetujui, TU Fakultas mengaktifkan akunnya.</p>
                        <div class="form-grid">
                            <div>
                                <label for="supervisor_name" class="label">Nama</label>
                                <input id="supervisor_name" name="supervisor_name" type="text" class="input" value="{{ old('supervisor_name') }}" maxlength="150">
                            </div>
                            <div>
                                <label for="supervisor_title" class="label">Jabatan</label>
                                <input id="supervisor_title" name="supervisor_title" type="text" class="input" value="{{ old('supervisor_title') }}" maxlength="100" placeholder="Misalnya Engineering Manager">
                            </div>
                            <div>
                                <label for="supervisor_email" class="label">Email</label>
                                <input id="supervisor_email" name="supervisor_email" type="email" class="input" value="{{ old('supervisor_email') }}" maxlength="150">
                            </div>
                            <div>
                                <label for="supervisor_phone" class="label">Nomor HP</label>
                                <input id="supervisor_phone" name="supervisor_phone" type="text" class="input" value="{{ old('supervisor_phone') }}" maxlength="30">
                            </div>
                        </div>
                    </div>

                    <div class="form-section" data-when="seminar">
                        <h3 class="form-section-title">Kegiatan seminar</h3>
                        <div class="form-grid">
                            <div class="span-all">
                                <label for="topic" class="label">Judul atau tema kegiatan</label>
                                <input id="topic" name="topic" type="text" class="input" value="{{ old('topic') }}" maxlength="200">
                            </div>
                            <div class="span-all">
                                <label for="organizer" class="label">Penyelenggara</label>
                                <input id="organizer" name="organizer" type="text" class="input" value="{{ old('organizer') }}" maxlength="150" placeholder="Misalnya Himpunan Mahasiswa Sistem Informasi">
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
