@extends('layouts.app')

@section('title', 'Kegiatan '.$internship->student->name)

@section('content')
    @php($student = $internship->student)
    {{-- Kepala halaman + profil dalam satu kartu tiga lapis (dipilih pemilik proyek):
         1) avatar, nama (judul halaman), status, Unduh; 2) empat kolom info berlabel; 3) empat kotak angka.
         Kampus, fakultas, semester ada di tooltip nama. --}}
    <?php
        $id = $student->academicIdentity();
        $taskPct = $stats['tasks'] > 0 ? (int) round($stats['done'] / $stats['tasks'] * 100) : 0;
        $division = $internship->division?->name ? 'Divisi '.$internship->division->name : null;
        // Company Supervisor sudah tahu perusahaannya sendiri: cukup divisi
        $isSupervisor = auth()->user()->role === 'supervisor';
    ?>
    <a href="{{ route('pantau.index') }}" class="prof-back"><x-ikon name="back" :size="16" /> Kembali ke daftar mahasiswa</a>
    <div class="card prof">
        {{-- Bagian atas bergradasi hijau merek (gaya banner dashboard) berisi nama dan info --}}
        <div class="prof-top">
        <svg class="prof-art" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><path d="M50 0C53 34 66 47 100 50 66 53 53 66 50 100 47 66 34 53 0 50 34 47 47 34 50 0z"/></svg>
        <svg class="prof-art prof-art-2" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><path d="M50 0C53 34 66 47 100 50 66 53 53 66 50 100 47 66 34 53 0 50 34 47 47 34 50 0z"/></svg>
        <div class="prof-head">
            <x-avatar :user="$student" class="prof-avatar" />
            <div class="prof-who" title="{{ $id['university'] }} · {{ $id['faculty'] }}{{ $id['semester'] ? ' · Semester '.$id['semester'] : '' }}">
                <div class="prof-title">
                    <h1 class="prof-name">{{ $student->name }}</h1>
                    <x-chip :tone="$internship->statusTone()">{{ $internship->statusLabel() }}</x-chip>
                </div>
                <div class="prof-sub">{{ $student->nim }} · {{ $id['program'] ?? 'Prodi belum diisi' }}@if ($id['entry_year']) · Angkatan {{ $id['entry_year'] }}@endif</div>
            </div>
            <a href="{{ route('portofolio.lihat', $student) }}" class="btn prof-glass prof-porto"><x-ikon name="star" :size="18" /> Portofolio</a>
            <details class="menu prof-menu">
                <summary class="btn prof-glass"><x-ikon name="download" :size="18" /> Unduh</summary>
                <div class="menu-panel">
                    <a href="{{ route('pantau.unduh', [$internship, 'format' => 'xlsx'] + (isset($filter) ? $filter->query() : [])) }}" class="menu-item"><x-ikon name="excel" :size="16" /> Excel (.xlsx)</a>
                    <a href="{{ route('pantau.unduh', [$internship, 'format' => 'pdf'] + (isset($filter) ? $filter->query() : [])) }}" target="_blank" rel="noopener" class="menu-item"><x-ikon name="print" :size="16" /> PDF (cetak)</a>
                    <p class="menu-note">{{ isset($filter) && $filter->isActive() ? 'Mengikuti filter yang aktif.' : 'Seluruh periode KP.' }}</p>
                </div>
            </details>
        </div>

        <dl class="prof-info">
            <div>
                <dt>{{ $isSupervisor ? 'Divisi' : 'Tempat' }}</dt>
                <dd>@if ($isSupervisor){{ $division ?? '–' }}@else{{ $internship->placeName() }}@if ($division)<small>{{ $division }}</small>@endif @endif</dd>
            </div>
            <div><dt>Posisi</dt><dd>{{ $internship->position ?? '–' }}</dd></div>
            <div><dt>Periode</dt><dd>{{ $internship->periodLabel() }}</dd></div>
            <div><dt>{{ $internship->lecturer?->roleLabelFor(auth()->user()) ?? 'Dosen pembimbing' }}</dt><dd>{{ $internship->lecturer?->listName() ?? '–' }}</dd></div>
        </dl>
        </div>

        <dl class="prof-stats">
            <div>
                <dt>Task selesai</dt>
                <dd>{{ $stats['done'] }}<small>/{{ $stats['tasks'] }}</small></dd>
                <span class="prof-bar" aria-hidden="true"><span style="width: {{ $taskPct }}%"></span></span>
            </div>
            <div>
                <dt>{{ $internship->status === 'berjalan' ? 'Minggu' : 'Durasi (minggu)' }}</dt>
                <dd>@if ($internship->status === 'berjalan'){{ $internship->currentWeek() }}<small>/{{ $internship->totalWeeks() }}</small>@else{{ $internship->totalWeeks() }}@endif</dd>
            </div>
            <div><dt>Rencana</dt><dd>{{ $stats['plans'] }}</dd></div>
            <div><dt>Hari tercatat</dt><dd>{{ $stats['days'] }}</dd></div>
        </dl>
        @php($canReplace = auth()->user()->role === 'supervisor' && $internship->isSupervisedBy(auth()->user()) && $internship->status === 'berjalan')
        @php($canRecommend = auth()->user()->role === 'supervisor' && $internship->isSupervisedBy(auth()->user()) && in_array($internship->status, ['berjalan', 'selesai'], true) && ! $internship->conversion)
        @if ($canCorrect || $canReplace || $canRecommend)
            {{-- Alat Company Supervisor: satu deret tombol, tiap form dibuka sebagai dialog --}}
            <div class="prof-tools">
                @if ($canCorrect)
                    <button type="button" class="btn btn-sm" data-open="dlg-posisi"><x-ikon name="edit" :size="16" /> Koreksi posisi</button>
                @endif
                @if ($canReplace)
                    <button type="button" class="btn btn-sm" data-open="dlg-supervisor"><x-ikon name="swap" :size="16" /> Ganti supervisor</button>
                @endif
                @if ($canRecommend)
                    <button type="button" class="btn btn-sm" data-open="dlg-rekrut"><x-ikon name="star" :size="16" /> Rekomendasikan rekrut</button>
                @endif
            </div>
        @endif
    </div>

    @if ($canCorrect)
        <dialog id="dlg-posisi" class="dialog" @if ($errors->has('position')) data-autoopen @endif>
            <form method="POST" action="{{ route('pantau.posisi', $internship) }}" class="dialog-body">
                @csrf
                @method('PATCH')
                <div class="dialog-head">
                    <h2 class="card-title">Koreksi posisi</h2>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div>
                    <label class="label" for="tool-position">Posisi magang</label>
                    <input id="tool-position" type="text" name="position" class="input" value="{{ old('position', $internship->position) }}" maxlength="100" required>
                    @error('position')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($canReplace)
        <dialog id="dlg-supervisor" class="dialog" @if ($errors->hasAny(['supervisor_name', 'supervisor_title', 'supervisor_email', 'reason'])) data-autoopen @endif>
            <form method="POST" action="{{ route('perubahan.supervisor', $internship) }}" class="dialog-body">
                @csrf
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Ganti supervisor</h2>
                        <p class="card-sub">Pengganti Anda diaktifkan TU Fakultas</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div class="stack">
                    <div class="form-grid">
                        <div>
                            <label class="label" for="tool-sv-name">Nama pengganti</label>
                            <input id="tool-sv-name" type="text" name="supervisor_name" class="input" maxlength="150" required value="{{ old('supervisor_name') }}">
                            @error('supervisor_name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="tool-sv-title">Jabatan <span class="muted">(opsional)</span></label>
                            <input id="tool-sv-title" type="text" name="supervisor_title" class="input" maxlength="100" value="{{ old('supervisor_title') }}">
                        </div>
                    </div>
                    <div>
                        <label class="label" for="tool-sv-email">Email kantor</label>
                        <input id="tool-sv-email" type="email" name="supervisor_email" class="input" maxlength="150" required value="{{ old('supervisor_email') }}">
                        @error('supervisor_email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="tool-sv-reason">Alasan</label>
                        <textarea id="tool-sv-reason" name="reason" class="textarea" rows="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                        @error('reason')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary"><x-ikon name="send" :size="16" /> Kirim ke TU</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($canRecommend)
        <dialog id="dlg-rekrut" class="dialog" @if ($errors->hasAny(['rekrutmen', 'posisi', 'catatan'])) data-autoopen @endif>
            <form method="POST" action="{{ route('rekrutmen.update', $internship) }}" class="dialog-body">
                @csrf
                <input type="hidden" name="langkah" value="rekomendasikan">
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Rekomendasikan rekrut</h2>
                        <p class="card-sub">Mahasiswa baru diberi tahu saat Anda menawarkan kerja</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div class="stack">
                    <div>
                        <label class="label" for="tool-rc-pos">Posisi yang cocok <span class="muted">(opsional)</span></label>
                        <input id="tool-rc-pos" type="text" name="posisi" class="input" maxlength="100" value="{{ old('posisi') }}">
                    </div>
                    <div>
                        <label class="label" for="tool-rc-note">Alasan singkat <span class="muted">(opsional)</span></label>
                        <textarea id="tool-rc-note" name="catatan" class="textarea" rows="3" maxlength="500">{{ old('catatan') }}</textarea>
                    </div>
                    @error('rekrutmen')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary"><x-ikon name="star" :size="16" /> Rekomendasikan</button>
                </div>
            </form>
        </dialog>
    @endif

    <nav class="tabs" aria-label="Bagian">
        <a href="{{ route('pantau.show', $internship) }}" @class(['tab', 'is-active' => $tab === 'kegiatan' && request('status') !== 'completed'])><x-ikon name="board" :size="16" /> Kegiatan</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'kegiatan', 'tampilan' => 'list', 'status' => 'completed']) }}" @class(['tab', 'is-active' => $tab === 'kegiatan' && request('status') === 'completed'])><x-ikon name="check" :size="16" /> Task selesai</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'rencana']) }}" @class(['tab', 'is-active' => $tab === 'rencana'])><x-ikon name="calendar" :size="16" /> Rencana</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'timesheet']) }}" @class(['tab', 'is-active' => $tab === 'timesheet'])><x-ikon name="clock" :size="16" /> Catatan</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'aktivitas']) }}" @class(['tab', 'is-active' => $tab === 'aktivitas'])><x-ikon name="activity" :size="16" /> Aktivitas</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'bimbingan']) }}" @class(['tab', 'is-active' => $tab === 'bimbingan'])><x-ikon name="chat" :size="16" /> Bimbingan</a>
    </nav>

    @include('pantau._confirmation', ['internship' => $internship])
    @include('pantau._rekrutmen', ['internship' => $internship])

    @if ($tab === 'kegiatan')
        <div class="toolbar board-toolbar">
            <div class="segmented">
                <a href="{{ route('pantau.show', [$internship] + $filter->query(['tampilan' => 'board'])) }}" @class(['seg', 'is-active' => $view === 'board'])><x-ikon name="board" :size="16" /> Board</a>
                <a href="{{ route('pantau.show', [$internship] + $filter->query(['tampilan' => 'list'])) }}" @class(['seg', 'is-active' => $view === 'list'])><x-ikon name="list" :size="16" /> List</a>
            </div>
            <div class="toolbar-stats">
                <span><strong>{{ $summary['total'] }}</strong> task</span>
                <span><strong>{{ $summary['done'] }}</strong> selesai</span>
                @if ($summary['overdue'] > 0)<span class="is-late"><strong>{{ $summary['overdue'] }}</strong> lewat tenggat</span>@endif
            </div>
        </div>
        @include('kegiatan._filters', ['action' => route('pantau.show', $internship)])
        @if ($view === 'board')
            @include('kegiatan._board', ['canEdit' => false])
        @else
            <section class="card">@include('kegiatan._list')</section>
        @endif
    @elseif ($tab === 'timesheet')
        <section class="card">
            <div class="card-head">
                <div class="period-nav">
                    <a href="{{ route('pantau.show', [$internship, 'tab' => 'timesheet', 'p' => $offset - 1]) }}" class="btn btn-sm btn-icon" aria-label="Periode sebelumnya"><x-ikon name="back" :size="18" /></a>
                    <div>
                        <h2 class="card-title">{{ $all ? 'Seluruh periode KP' : $period->label }}</h2>
                        <p class="card-sub">{{ $entries->count() }} catatan kegiatan</p>
                    </div>
                    @if ($offset < 0)
                        <a href="{{ route('pantau.show', [$internship, 'tab' => 'timesheet', 'p' => $offset + 1]) }}" class="btn btn-sm btn-icon" aria-label="Periode berikutnya"><x-ikon name="arrow" :size="18" /></a>
                    @endif
                </div>
                <div class="segmented">
                    <a href="{{ route('pantau.show', [$internship, 'tab' => 'timesheet']) }}" @class(['seg', 'is-active' => ! $all && $offset === 0])>Periode ini</a>
                    <a href="{{ route('pantau.show', [$internship, 'tab' => 'timesheet', 'periode' => 'semua']) }}" @class(['seg', 'is-active' => $all])>Semua</a>
                </div>
            </div>
            @if ($days->isEmpty())
                <div class="empty"><strong>Belum ada catatan</strong> Mahasiswa belum mencatat kegiatan di periode ini.</div>
            @else
                <div class="days">
                    @foreach ($days as $date => $items)
                        @php($day = \Illuminate\Support\Carbon::parse($date)->locale('id'))
                        <div class="day">
                            <div class="day-head">
                                <span class="day-date"><strong>{{ $day->translatedFormat('l') }}</strong>, {{ $day->translatedFormat('j F Y') }}</span>
                                <span class="day-total">{{ $items->count() }} catatan</span>
                            </div>
                            <ul class="time-list">
                                @foreach ($items as $entry)
                                    <li>
                                        <span class="time-dot" aria-hidden="true"></span>
                                        <div class="grow min0">
                                            <div class="time-desc">{{ $entry->description }}</div>
                                            @if ($entry->task)
                                                <a href="{{ route('tugas.show', $entry->task) }}" class="cell-sub">{{ $entry->task->key() }} · {{ $entry->task->title }}</a>
                                            @endif
                                        </div>
                                        @if ($entry->photo)
                                            <a href="{{ route('timesheet.foto', $entry) }}" target="_blank" rel="noopener" class="time-photo"><img src="{{ route('timesheet.foto', $entry) }}" alt="Foto dokumentasi" loading="lazy" width="44" height="44"></a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @elseif ($tab === 'rencana')
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Rencana kegiatan</h2>
                    <p class="card-sub">{{ $internship->periodLabel() }} · {{ $internship->totalWeeks() }} minggu</p>
                </div>
            </div>
            @if ($plans->isEmpty())
                <div class="empty"><strong>Belum ada rencana</strong> Mahasiswa menyusun rencana kegiatan di timesheet sebelum membuat task.</div>
            @else
                @include('timesheet._gantt', ['canEdit' => false])
            @endif
        </section>
        @include('kegiatan._goals', ['canEdit' => false])
    @elseif ($tab === 'aktivitas')
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Aktivitas</h2>
                    <p class="card-sub">60 aktivitas terakhir mahasiswa</p>
                </div>
            </div>
            @if ($feed->isEmpty())
                <div class="empty"><strong>Belum ada aktivitas</strong> Aktivitas muncul saat mahasiswa menyusun rencana, task, dan catatan.</div>
            @else
                @include('aktivitas._feed', ['days' => \App\Support\ActivityFeed::byDay($feed), 'showStudent' => false])
            @endif
        </section>
    @elseif ($tab === 'bimbingan')
        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Log bimbingan</h2>
                    <p class="card-sub">Dicatat mahasiswa, dikonfirmasi dosen pembimbing</p>
                </div>
                <x-chip :tone="$sessions->where('status', 'confirmed')->count() >= $min ? 'green' : 'gray'">{{ $sessions->where('status', 'confirmed')->count() }} / {{ $min }} terkonfirmasi</x-chip>
            </div>
            @include('bimbingan._list', ['sessions' => $sessions, 'canDecide' => $canDecide])
        </section>
    @endif
@endsection
