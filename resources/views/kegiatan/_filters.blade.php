{{-- Baris filter board/list. Variabel: $filter, $projects, $view, $sort, $currentPeriod, $action (url) --}}
@php($v = $filter->values)
<form method="GET" action="{{ $action }}" class="filters" data-autosubmit>
    <input type="hidden" name="tampilan" value="{{ $view }}">
    <div class="filter-search">
        <x-ikon name="search" :size="16" />
        <input type="search" name="cari" value="{{ $v['cari'] ?? '' }}" placeholder="Cari task…" aria-label="Cari task" class="input input-sm">
    </div>
    <select name="periode" class="select select-sm" aria-label="Periode">
        <option value="semua" @selected($v['periode'] === 'semua')>Semua periode</option>
        <option value="ini" @selected($v['periode'] === 'ini')>Periode ini · {{ $currentPeriod->short }}</option>
        <option value="lalu" @selected($v['periode'] === 'lalu')>Periode lalu</option>
        <option value="rentang" @selected($v['periode'] === 'rentang')>Rentang tanggal…</option>
    </select>
    @if ($v['periode'] === 'rentang')
        <input type="date" name="dari" value="{{ $v['dari'] ?? '' }}" class="input input-sm" aria-label="Dari tanggal">
        <input type="date" name="sampai" value="{{ $v['sampai'] ?? '' }}" class="input input-sm" aria-label="Sampai tanggal">
    @endif
    <select name="proyek" class="select select-sm" aria-label="Proyek">
        <option value="">Semua proyek</option>
        @foreach ($projects as $project)
            <option value="{{ $project->id }}" @selected(($v['proyek'] ?? null) === $project->id)>{{ $project->key }} · {{ $project->name }}{{ $project->isArchived() ? ' (arsip)' : '' }}</option>
        @endforeach
    </select>
    <select name="status" class="select select-sm" aria-label="Status">
        <option value="">Semua status</option>
        @foreach (\App\Models\WeeklyTask::STATUSES as $key => [$label])
            <option value="{{ $key }}" @selected(($v['status'] ?? null) === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="prioritas" class="select select-sm" aria-label="Prioritas">
        <option value="">Semua prioritas</option>
        @foreach (\App\Models\WeeklyTask::PRIORITIES as $key => [$label])
            <option value="{{ $key }}" @selected(($v['prioritas'] ?? null) === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="kategori" class="select select-sm" aria-label="Kategori silabus">
        <option value="">Semua kategori</option>
        @foreach (\App\Models\SyllabusItem::CATEGORIES as $key => [$label])
            <option value="{{ $key }}" @selected(($v['kategori'] ?? null) === $key)>{{ $label }}</option>
        @endforeach
    </select>
    @if ($view === 'list')
        <select name="urut" class="select select-sm" aria-label="Urutkan">
            @foreach (['tenggat' => 'Urut: tenggat', 'prioritas' => 'Urut: prioritas', 'terbaru' => 'Urut: terakhir diubah', 'kode' => 'Urut: terbaru dibuat'] as $key => $label)
                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
            @endforeach
        </select>
    @endif
    <noscript><button type="submit" class="btn btn-sm">Terapkan</button></noscript>
    @if ($filter->isActive())
        <a href="{{ $action }}?tampilan={{ $view }}" class="btn btn-sm btn-ghost">Reset</a>
    @endif
</form>
