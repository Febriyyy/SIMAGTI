@extends('layouts.app')

@section('title', 'Kegiatan')

@section('content')
    @if (! $internship)
        <div class="page-head">
            <div>
                <h1 class="page-title">Kegiatan</h1>
                <p class="page-sub">Board pekerjaan selama KP.</p>
            </div>
        </div>
        <div class="card">
            <div class="empty">
                <strong>Belum bisa dipakai</strong>
                Board kegiatan aktif setelah pengajuan KP Anda disetujui. <a href="{{ route('kp.status') }}">Lihat status KP</a>.
            </div>
        </div>
    @else
        <div class="page-head">
            <div>
                <h1 class="page-title">Kegiatan</h1>
                <p class="page-sub">{{ $internship->placeName() }} · ritme {{ mb_strtolower(\App\Models\Internship::CADENCES[$internship->cadence]) }} · {{ $currentPeriod->label }}</p>
            </div>
            @if ($canEdit)
                <button type="button" class="btn btn-primary" data-open="dlg-task"><x-ikon name="plus" :size="18" /> Task baru</button>
            @endif
        </div>

        <div class="toolbar board-toolbar">
            <div class="segmented" role="tablist" aria-label="Tampilan">
                <a href="{{ route('kegiatan.index', $filter->query(['tampilan' => 'board'])) }}" @class(['seg', 'is-active' => $view === 'board']) role="tab" aria-selected="{{ $view === 'board' ? 'true' : 'false' }}"><x-ikon name="board" :size="16" /> Board</a>
                <a href="{{ route('kegiatan.index', $filter->query(['tampilan' => 'list'])) }}" @class(['seg', 'is-active' => $view === 'list']) role="tab" aria-selected="{{ $view === 'list' ? 'true' : 'false' }}"><x-ikon name="list" :size="16" /> List</a>
            </div>
            <div class="toolbar-stats">
                <span><strong>{{ $summary['total'] }}</strong> task</span>
                <span><strong>{{ $summary['done'] }}</strong> selesai</span>
                @if ($summary['overdue'] > 0)
                    <span class="is-late"><strong>{{ $summary['overdue'] }}</strong> lewat tenggat</span>
                @endif
                <span><strong>{{ \App\Support\Num::short($summary['hours']) }}</strong> jam</span>
            </div>
            <div class="toolbar-actions">
                @if ($canEdit)
                    <button type="button" class="btn btn-sm" data-open="dlg-projects"><x-ikon name="project" :size="16" /> Proyek</button>
                    <button type="button" class="btn btn-sm" data-open="dlg-columns"><x-ikon name="board" :size="16" /> Atur kolom</button>
                @endif
                <details class="menu">
                    <summary class="btn btn-sm"><x-ikon name="download" :size="16" /> Unduh</summary>
                    <div class="menu-panel">
                        <a href="{{ route('kegiatan.unduh', $filter->query(['format' => 'xlsx'])) }}" class="menu-item"><x-ikon name="excel" :size="16" /> Excel (.xlsx)</a>
                        <a href="{{ route('kegiatan.unduh', $filter->query(['format' => 'pdf'])) }}" target="_blank" rel="noopener" class="menu-item"><x-ikon name="print" :size="16" /> PDF (cetak)</a>
                        <p class="menu-note">Mengikuti filter yang sedang aktif.</p>
                    </div>
                </details>
            </div>
        </div>

        @include('kegiatan._filters', ['action' => route('kegiatan.index')])

        @if ($view === 'board')
            @include('kegiatan._board')
        @else
            <section class="card">@include('kegiatan._list')</section>
        @endif

        @if ($canEdit)
            {{-- Task baru --}}
            <dialog id="dlg-task" class="dialog">
                <form method="POST" action="{{ route('kegiatan.tugas.store') }}" class="dialog-body">
                    @csrf
                    <div class="dialog-head">
                        <h2 class="card-title">Task baru</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                    </div>
                    @include('kegiatan._task-form', ['task' => null])
                    <div class="dialog-foot">
                        <label class="check"><input type="checkbox" name="buka" value="1"> Buka task setelah dibuat</label>
                        <button type="submit" class="btn btn-primary">Simpan task</button>
                    </div>
                </form>
            </dialog>

            {{-- Proyek --}}
            <dialog id="dlg-projects" class="dialog">
                <div class="dialog-body">
                    <div class="dialog-head">
                        <h2 class="card-title">Proyek</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                    </div>
                    @if ($projects->isEmpty())
                        <p class="muted">Belum ada proyek. Proyek mengelompokkan task dan memberi kode seperti PMS-12.</p>
                    @else
                        <ul class="item-list bordered">
                            @foreach ($projects as $project)
                                <li class="item-row">
                                    <span class="tag tag-{{ $project->color }}">{{ $project->key }}</span>
                                    <div class="grow min0">
                                        <div class="cell-title truncate">{{ $project->name }}</div>
                                        <div class="cell-sub">{{ $project->isArchived() ? 'Diarsipkan' : 'Aktif' }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('kegiatan.proyek.update', $project) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="archived" value="{{ $project->isArchived() ? 0 : 1 }}">
                                        <button type="submit" class="btn btn-sm btn-ghost">{{ $project->isArchived() ? 'Aktifkan' : 'Arsipkan' }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('kegiatan.proyek.store') }}" class="form-grid" style="margin-top: var(--space-4)">
                        @csrf
                        <div>
                            <label class="label" for="pj-name">Nama proyek</label>
                            <input id="pj-name" type="text" name="name" class="input" maxlength="120" required placeholder="Modul Pemesanan">
                        </div>
                        <div>
                            <label class="label" for="pj-key">Kode (opsional)</label>
                            <input id="pj-key" type="text" name="key" class="input" maxlength="6" placeholder="PMS" style="text-transform: uppercase">
                        </div>
                        <div class="span-all"><button type="submit" class="btn btn-primary btn-sm">Tambah proyek</button></div>
                    </form>
                </div>
            </dialog>

            {{-- Kolom --}}
            <dialog id="dlg-columns" class="dialog">
                <div class="dialog-body">
                    <div class="dialog-head">
                        <h2 class="card-title">Atur kolom board</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                    </div>
                    <p class="hint">Nama kolom bebas. "Arti" dipakai sistem untuk menghitung progres: task di kolom berarti <em>Selesai</em> dihitung selesai.</p>
                    <ul class="item-list bordered">
                        @foreach ($columns as $i => $column)
                            <li class="item-row">
                                <form method="POST" action="{{ route('kegiatan.kolom.update', $column) }}" class="column-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" class="input input-sm" value="{{ $column->name }}" maxlength="60" required aria-label="Nama kolom">
                                    <select name="category" class="select select-sm" aria-label="Arti kolom">
                                        @foreach (\App\Models\BoardColumn::CATEGORIES as $key => [$label])
                                            <option value="{{ $key }}" @selected($column->category === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="position" class="select select-sm" aria-label="Urutan">
                                        @foreach ($columns as $j => $c)
                                            <option value="{{ $j }}" @selected($i === $j)>Urutan {{ $j + 1 }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm">Simpan</button>
                                </form>
                                <form method="POST" action="{{ route('kegiatan.kolom.destroy', $column) }}" onsubmit="return confirm('Hapus kolom {{ $column->name }}? Task di dalamnya dipindah ke kolom lain.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" aria-label="Hapus kolom {{ $column->name }}"><x-ikon name="delete" :size="16" /></button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('kegiatan.kolom.store') }}" class="column-form" style="margin-top: var(--space-4)">
                        @csrf
                        <input type="text" name="name" class="input input-sm" maxlength="60" required placeholder="Nama kolom baru" aria-label="Nama kolom baru">
                        <select name="category" class="select select-sm" aria-label="Arti kolom">
                            @foreach (\App\Models\BoardColumn::CATEGORIES as $key => [$label])
                                <option value="{{ $key }}" @selected($key === 'in_progress')>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Tambah</button>
                    </form>

                    <hr class="divider">
                    <form method="POST" action="{{ route('kegiatan.pengaturan') }}" class="column-form">
                        @csrf
                        @method('PATCH')
                        <label class="label" for="cadence" style="margin: 0">Ritme kerja (periode)</label>
                        <select id="cadence" name="cadence" class="select select-sm">
                            @foreach (\App\Models\Internship::CADENCES as $key => $label)
                                <option value="{{ $key }}" @selected($internship->cadence === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm">Simpan</button>
                    </form>
                    <p class="hint">Menentukan arti "periode ini" di filter dan timesheet, sesuai kesepakatan dengan perusahaan.</p>
                </div>
            </dialog>
        @endif
    @endif
@endsection
