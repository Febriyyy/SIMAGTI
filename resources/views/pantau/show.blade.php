@extends('layouts.app')

@section('title', 'Kegiatan '.$internship->student->name)

@section('content')
    @php($student = $internship->student)
    <div class="page-head">
        <div class="min0">
            <a href="{{ route('pantau.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke daftar mahasiswa</a>
            <h1 class="page-title">{{ $student->name }}</h1>
            <p class="page-sub">{{ $student->nim }} · {{ $internship->placeName() }}@if ($internship->placeDetail()) · {{ $internship->placeDetail() }}@endif</p>
        </div>
        <details class="menu">
            <summary class="btn"><x-ikon name="download" :size="18" /> Unduh</summary>
            <div class="menu-panel">
                <a href="{{ route('pantau.unduh', [$internship, 'format' => 'xlsx'] + (isset($filter) ? $filter->query() : [])) }}" class="menu-item"><x-ikon name="excel" :size="16" /> Excel (.xlsx)</a>
                <a href="{{ route('pantau.unduh', [$internship, 'format' => 'pdf'] + (isset($filter) ? $filter->query() : [])) }}" target="_blank" rel="noopener" class="menu-item"><x-ikon name="print" :size="16" /> PDF (cetak)</a>
                <p class="menu-note">{{ isset($filter) && $filter->isActive() ? 'Mengikuti filter yang aktif.' : 'Seluruh periode KP.' }}</p>
            </div>
        </details>
    </div>

    <div class="profile-strip card">
        <div class="profile-strip-main">
            <x-avatar :user="$student" />
            <div class="min0">
                <div class="cell-title">{{ $student->name }}</div>
                @php($id = $student->academicIdentity())
                <div class="cell-sub">{{ $id['university'] }} · {{ $id['faculty'] }}</div>
                <div class="cell-sub">{{ $id['program'] ?? 'Prodi belum diisi' }}@if ($id['entry_year']) · Angkatan {{ $id['entry_year'] }}@endif @if ($id['semester']) · Semester {{ $id['semester'] }}@endif</div>
            </div>
        </div>
        <dl class="profile-strip-facts">
            <div><dt>{{ $internship->isSeminar() ? 'Jenis' : 'Posisi' }}</dt><dd>{{ $internship->isSeminar() ? $internship->programTypeLabel() : ($internship->position ?? '–') }}</dd></div>
            <div><dt>Periode</dt><dd>{{ $internship->periodLabel() }}<small>{{ $internship->durationMonths() }} bulan · {{ $internship->status === 'berjalan' ? 'sisa '.$internship->daysLeft().' hari' : $internship->statusLabel() }}</small></dd></div>
            <div><dt>Task selesai</dt><dd>{{ $stats['done'] }} / {{ $stats['tasks'] }}</dd></div>
            <div><dt>Jam tercatat</dt><dd>{{ \App\Support\Num::short($stats['hours']) }} jam<small>{{ $stats['days'] }} hari</small></dd></div>
            <div><dt>{{ $internship->lecturer?->roleLabelFor(auth()->user()) ?? 'Dosen pembimbing' }}</dt><dd>{{ $internship->lecturer?->name ?? '–' }}</dd></div>
        </dl>
        @if ($canCorrect)
            <details class="inline-details profile-strip-edit">
                <summary class="btn btn-sm"><x-ikon name="edit" :size="16" /> Koreksi posisi</summary>
                <form method="POST" action="{{ route('pantau.posisi', $internship) }}" class="inline-form">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="position" class="input input-sm" value="{{ $internship->position }}" maxlength="100" required aria-label="Posisi magang">
                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                </form>
            </details>
        @endif
    </div>

    <nav class="tabs" aria-label="Bagian">
        <a href="{{ route('pantau.show', $internship) }}" @class(['tab', 'is-active' => $tab === 'kegiatan' && request('status') !== 'completed'])><x-ikon name="board" :size="16" /> Kegiatan</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'kegiatan', 'tampilan' => 'list', 'status' => 'completed']) }}" @class(['tab', 'is-active' => $tab === 'kegiatan' && request('status') === 'completed'])><x-ikon name="check" :size="16" /> Task selesai</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'timesheet']) }}" @class(['tab', 'is-active' => $tab === 'timesheet'])><x-ikon name="clock" :size="16" /> Timesheet</a>
        <a href="{{ route('pantau.show', [$internship, 'tab' => 'silabus']) }}" @class(['tab', 'is-active' => $tab === 'silabus'])>
            <x-ikon name="syllabus" :size="16" /> Silabus
            @if ($internship->syllabus_status === 'submitted')<span class="tab-dot" title="Menunggu persetujuan"></span>@endif
        </a>
    </nav>

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
                <span><strong>{{ \App\Support\Num::short($summary['hours']) }}</strong> jam</span>
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
                        <p class="card-sub">{{ $entries->count() }} catatan · {{ \App\Support\Num::short((float) $entries->sum('hours')) }} jam</p>
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
                                <span class="day-total">{{ \App\Support\Num::short((float) $items->sum('hours')) }} jam</span>
                            </div>
                            <ul class="time-list">
                                @foreach ($items as $entry)
                                    <li>
                                        <span class="time-hours">{{ \App\Support\Num::short((float) $entry->hours) }} j</span>
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
    @else
        @if ($internship->syllabus_status === 'submitted' && $canReviewSyllabus)
            <section class="card callout">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Silabus menunggu persetujuan Anda</h2>
                        <p class="card-sub">Setujui jika tujuan sudah sesuai rencana magang, atau minta revisi dengan catatan.</p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('silabus.review', $internship) }}" class="stack">
                        @csrf
                        @method('PATCH')
                        <textarea name="catatan" class="textarea" rows="2" maxlength="1000" placeholder="Catatan untuk mahasiswa (wajib jika meminta revisi)" aria-label="Catatan"></textarea>
                        <div class="inline-form">
                            <button type="submit" name="keputusan" value="setujui" class="btn btn-primary"><x-ikon name="check" :size="18" /> Setujui silabus</button>
                            <button type="submit" name="keputusan" value="revisi" class="btn">Minta revisi</button>
                        </div>
                    </form>
                </div>
            </section>
        @else
            <div class="alert alert-{{ ['approved' => 'success', 'revision' => 'warning', 'submitted' => 'info'][$internship->syllabus_status] ?? 'info' }}" role="status">
                Status silabus: <strong>{{ $internship->syllabusStatusLabel() }}</strong>
                @if ($internship->syllabus_note) · Catatan: {{ $internship->syllabus_note }} @endif
            </div>
        @endif
        @include('silabus._items', ['internship' => $internship, 'canEdit' => false])
    @endif
@endsection
