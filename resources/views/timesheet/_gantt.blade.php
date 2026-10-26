{{--
    Gantt rencana kegiatan (acuan: tampilan Gantt aplikasi manajemen proyek).
    Kiri: tabel Kegiatan / Durasi / Status (menempel saat digeser). Kanan: sumbu hari sepanjang periode KP, akhir pekan diarsir,
    garis merah hari ini, batang berwarna per rencana dengan task di bawahnya (bisa dilipat).
    Variabel: $gantt (App\Support\Gantt::build), $canEdit (mahasiswa pemilik), $compact (ringkas untuk dashboard, opsional).
    Zoom (Hari / Minggu / Penuh) dan tampilan (Gantt / Daftar) diatur public/js/app.js tanpa memuat ulang halaman.
--}}
@php
    $compact = $compact ?? false;
    $firstMonday = collect($gantt['days'])->search(fn ($d) => $d['monday']);
@endphp
<div @class(['gt', 'gt-compact' => $compact, 'gt-readonly' => ! $canEdit]) data-gantt data-zoom="week" data-view="gantt"
     style="--days: {{ $gantt['total'] }}; --mon: {{ $firstMonday === false ? 0 : $firstMonday }}">
    @unless ($compact)
        <div class="gt-toolbar">
            <div class="segmented" role="group" aria-label="Tampilan">
                <button type="button" class="seg is-active" data-gt-view="gantt" aria-pressed="true"><x-ikon name="calendar" :size="16" /> Gantt</button>
                <button type="button" class="seg" data-gt-view="list" aria-pressed="false"><x-ikon name="list" :size="16" /> Daftar</button>
            </div>
            <div class="gt-tools">
                <div class="segmented gt-zoom" role="group" aria-label="Skala waktu">
                    <button type="button" class="seg" data-gt-zoom="day" aria-pressed="false">Hari</button>
                    <button type="button" class="seg is-active" data-gt-zoom="week" aria-pressed="true">Minggu</button>
                    <button type="button" class="seg" data-gt-zoom="fit" aria-pressed="false">Penuh</button>
                </div>
                <div class="gt-nav">
                    <button type="button" class="btn btn-sm btn-icon" data-gt-step="-7" aria-label="Geser seminggu ke kiri"><x-ikon name="back" :size="16" /></button>
                    @if ($gantt['today'] !== null)
                        <button type="button" class="btn btn-sm" data-gt-today>Hari ini</button>
                    @endif
                    <button type="button" class="btn btn-sm btn-icon" data-gt-step="7" aria-label="Geser seminggu ke kanan"><x-ikon name="next" :size="16" /></button>
                </div>
            </div>
        </div>
    @endunless

    <div class="gt-scroll" data-gt-scroll @if ($gantt['today'] !== null) data-today="{{ $gantt['today'] }}" @endif>
        <div class="gt-grid">
            <div class="gt-head">
                <div class="gt-side gt-cols">
                    <span class="gt-c-lead" aria-hidden="true"></span>
                    <span class="gt-c-name">Kegiatan</span>
                    <span class="gt-c-dur">Durasi</span>
                    <span class="gt-c-status">Status</span>
                    <span class="gt-c-act" aria-hidden="true"></span>
                </div>
                <div class="gt-time" aria-hidden="true">
                    <div class="gt-months">
                        @foreach ($gantt['months'] as $m)
                            <span style="--o: {{ $m['start'] }}; --n: {{ $m['length'] }}" title="{{ $m['label'] }}"><b>{{ $m['length'] >= 6 ? $m['label'] : '' }}</b></span>
                        @endforeach
                    </div>
                    <div class="gt-days">
                        @foreach ($gantt['days'] as $d)
                            <span @class(['gt-day', 'is-weekend' => $d['weekend'], 'is-monday' => $d['monday'], 'is-today' => $d['today']]) title="{{ $d['title'] }}"><i>{{ $d['weekday'] }}</i><b>{{ $d['day'] }}</b></span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="gt-body">
                <div class="gt-bg" aria-hidden="true">
                    @foreach ($gantt['days'] as $i => $d)
                        @if ($d['weekend'])<i class="gt-wk" style="--o: {{ $i }}"></i>@endif
                    @endforeach
                    @if ($gantt['today'] !== null)<i class="gt-now" style="--o: {{ $gantt['today'] }}"><span>Hari ini</span></i>@endif
                </div>

                @foreach ($gantt['rows'] as $row)
                    @php($plan = $row['plan'])
                    @php($hasTasks = count($row['tasks']) > 0)
                    <div @class(['gt-row', 'gt-plan', 'is-late' => $row['late']]) data-gt-plan="{{ $plan->id }}" style="--c: var(--gc-{{ $row['color'] }})">
                        <div class="gt-side">
                            <span class="gt-c-lead">
                                @if ($hasTasks)
                                    <button type="button" class="gt-caret" data-gt-toggle="{{ $plan->id }}" aria-expanded="true" aria-label="Lipat task {{ $plan->title }}"><x-ikon name="expand" :size="16" /></button>
                                @else
                                    <span class="gt-dot" aria-hidden="true"></span>
                                @endif
                            </span>
                            <span class="gt-c-name">
                                <span class="gt-title" title="{{ $plan->title }}">{{ $plan->title }}</span>
                                <span class="gt-sub">{{ $plan->rangeLabel() }}</span>
                            </span>
                            <span class="gt-c-dur">{{ $plan->days() }} hari</span>
                            <span class="gt-c-status">
                                @if ($plan->tasks_count > 0)
                                    <span class="gt-prog" role="progressbar" aria-valuenow="{{ $row['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Task selesai"><span style="width: {{ $row['percent'] }}%"></span></span>
                                    <b>{{ $row['percent'] }}%</b>
                                @else
                                    <small>Belum ada task</small>
                                @endif
                            </span>
                            <span class="gt-c-act">
                                @if ($canEdit)
                                    <a href="{{ route('kegiatan.index', ['rencana' => $plan->id, 'baru' => 1]) }}" class="btn btn-sm btn-icon btn-ghost" title="Tambah task di rencana ini" aria-label="Tambah task di {{ $plan->title }}"><x-ikon name="plus" :size="16" /></a>
                                @endif
                            </span>
                        </div>
                        <div class="gt-track">
                            @php($tag = $canEdit ? 'button' : 'span')
                            <{{ $tag }} @if ($canEdit) type="button" data-open="dlg-plan-{{ $plan->id }}" @endif
                                @class(['gt-bar', 'is-'.$row['phase'], 'is-empty' => $plan->tasks_count === 0, 'is-done' => $plan->tasks_count > 0 && $row['percent'] === 100, 'is-late' => $row['late']])
                                style="--o: {{ $row['offset'] }}; --n: {{ $row['length'] }}"
                                title="{{ $plan->title }} · {{ $plan->rangeLabel() }}{{ $plan->tasks_count > 0 ? ' · '.$row['percent'].'% task selesai' : '' }}{{ $canEdit ? ' · klik untuk mengubah' : '' }}">
                                <span class="gt-fill" style="width: {{ $row['percent'] }}%"></span>
                                <span class="gt-label">{{ $plan->title }}</span>
                            </{{ $tag }}>
                        </div>
                    </div>

                    @foreach ($row['tasks'] as $t)
                        @php($task = $t['task'])
                        <div @class(['gt-row', 'gt-task', 'is-done' => $t['done']]) data-gt-parent="{{ $plan->id }}" style="--c: var(--gc-{{ $row['color'] }})">
                            <div class="gt-side">
                                <span class="gt-c-lead">
                                    <span @class(['gt-check', 'is-done' => $t['done']]) aria-label="{{ $t['done'] ? 'Selesai' : 'Belum selesai' }}">@if ($t['done'])<x-ikon name="tick" :size="12" />@endif</span>
                                </span>
                                <a href="{{ route('tugas.show', $task) }}" class="gt-c-name">
                                    <span class="gt-title" title="{{ $task->title }}">{{ $task->title }}</span>
                                    <span class="gt-sub">{{ $task->key() }}</span>
                                </a>
                                <span class="gt-c-dur">{{ $t['days'] ? $t['days'].' hari' : '–' }}</span>
                                <span class="gt-c-status">
                                    <span @class(['gt-state', 'is-late' => $t['late']])><i class="tone-{{ $t['late'] ? 'red' : ($task->column?->tone() ?? $task->statusTone()) }}"></i>{{ $t['late'] ? 'Terlambat' : ($task->column?->name ?? $task->statusLabel()) }}</span>
                                </span>
                                <span class="gt-c-act" aria-hidden="true"></span>
                            </div>
                            <div class="gt-track">
                                @if ($t['offset'] !== null)
                                    <a href="{{ route('tugas.show', $task) }}" @class(['gt-bar', 'gt-bar-task', 'is-done' => $t['done'], 'is-late' => $t['late']])
                                       style="--o: {{ $t['offset'] }}; --n: {{ $t['length'] }}" title="{{ $task->key() }} · {{ $task->title }}" tabindex="-1">
                                        @if ($t['done'])<x-ikon name="tick" :size="12" />@endif
                                        <span class="gt-label">{{ $task->title }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>

    <div class="gt-legend">
        <span><i class="gt-lg-now"></i> Hari ini</span>
        <span><i class="gt-lg-wk"></i> Akhir pekan</span>
        <span><i class="gt-lg-fill"></i> Bagian gelap = task selesai</span>
        @unless ($compact)<span><i class="gt-lg-late"></i> Lewat waktu, task belum selesai</span>@endunless
    </div>
</div>
