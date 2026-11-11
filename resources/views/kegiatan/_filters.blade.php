{{--
    Baris filter board/list: kotak cari + satu tombol "Filter" yang membuka pop-up berisi semua pilihan.
    Pilihan di pop-up baru diterapkan saat tombol Terapkan ditekan (kotak cari tetap langsung mencari).
    Variabel: $filter, $projects, $plans, $goals, $view, $sort, $currentPeriod, $action (url)
--}}
@php($v = $filter->values)
@php($activeFilters = collect(['proyek', 'status', 'prioritas', 'rencana', 'goal'])->filter(fn ($k) => filled($v[$k] ?? null))->count() + (($v['periode'] ?? 'semua') !== 'semua' ? 1 : 0))
<form method="GET" action="{{ $action }}" class="filters" data-autosubmit data-filters>
    <input type="hidden" name="tampilan" value="{{ $view }}">
    <div class="filter-search">
        <x-ikon name="search" :size="16" />
        <input type="search" name="cari" value="{{ $v['cari'] ?? '' }}" placeholder="Cari task…" aria-label="Cari task" class="input input-sm">
    </div>

    <div class="filter-pop">
        <button type="button" @class(['btn', 'btn-sm', 'filter-toggle', 'is-active' => $activeFilters > 0]) data-filter-toggle aria-expanded="false" aria-controls="filter-panel" aria-haspopup="true">
            <x-ikon name="filter" :size="16" /> Filter @if ($activeFilters > 0)<span class="filter-count" aria-label="{{ $activeFilters }} filter aktif">{{ $activeFilters }}</span>@endif
        </button>

        <div class="filter-panel" id="filter-panel" role="group" aria-label="Pilihan filter">
            <div class="filter-panel-head">
                <strong>Filter task</strong>
                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-filter-close aria-label="Tutup filter"><x-ikon name="close" :size="16" /></button>
            </div>

            <div class="filter-fields">
                <div class="field">
                    <label class="label" for="f-periode">Periode</label>
                    <select id="f-periode" name="periode" class="select select-sm" data-range-toggle>
                        <option value="semua" @selected($v['periode'] === 'semua')>Semua periode</option>
                        <option value="ini" @selected($v['periode'] === 'ini')>Periode ini · {{ $currentPeriod->short }}</option>
                        <option value="lalu" @selected($v['periode'] === 'lalu')>Periode lalu</option>
                        <option value="rentang" @selected($v['periode'] === 'rentang')>Rentang tanggal…</option>
                    </select>
                </div>
                <div class="field filter-range" @if ($v['periode'] !== 'rentang') hidden @endif>
                    <span class="label">Rentang tanggal</span>
                    <div class="filter-range-inputs">
                        <input type="date" name="dari" value="{{ $v['dari'] ?? '' }}" class="input input-sm" aria-label="Dari tanggal" @disabled($v['periode'] !== 'rentang')>
                        <input type="date" name="sampai" value="{{ $v['sampai'] ?? '' }}" class="input input-sm" aria-label="Sampai tanggal" @disabled($v['periode'] !== 'rentang')>
                    </div>
                </div>
                <div class="field">
                    <label class="label" for="f-proyek">Proyek</label>
                    <select id="f-proyek" name="proyek" class="select select-sm">
                        <option value="">Semua proyek</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(($v['proyek'] ?? null) === $project->id)>{{ $project->key }} · {{ $project->name }}{{ $project->isArchived() ? ' (arsip)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="f-status">Status</label>
                    <select id="f-status" name="status" class="select select-sm">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\WeeklyTask::STATUSES as $key => [$label])
                            <option value="{{ $key }}" @selected(($v['status'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="f-prioritas">Prioritas</label>
                    <select id="f-prioritas" name="prioritas" class="select select-sm">
                        <option value="">Semua prioritas</option>
                        @foreach (\App\Models\WeeklyTask::PRIORITIES as $key => [$label])
                            <option value="{{ $key }}" @selected(($v['prioritas'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if (isset($plans) && $plans->isNotEmpty())
                    <div class="field">
                        <label class="label" for="f-rencana">Rencana kegiatan</label>
                        <select id="f-rencana" name="rencana" class="select select-sm">
                            <option value="">Semua rencana</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(($v['rencana'] ?? null) === $plan->id)>{{ \Illuminate\Support\Str::limit($plan->title, 40) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if (isset($goals) && $goals->isNotEmpty())
                    <div class="field">
                        <label class="label" for="f-goal">Goal</label>
                        <select id="f-goal" name="goal" class="select select-sm">
                            <option value="">Semua goal</option>
                            @foreach ($goals as $goal)
                                <option value="{{ $goal->id }}" @selected(($v['goal'] ?? null) === $goal->id)>{{ \Illuminate\Support\Str::limit($goal->title, 40) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($view === 'list')
                    <div class="field">
                        <label class="label" for="f-urut">Urutkan</label>
                        <select id="f-urut" name="urut" class="select select-sm">
                            @foreach (['tenggat' => 'Urut: tenggat', 'prioritas' => 'Urut: prioritas', 'terbaru' => 'Urut: terakhir diubah', 'kode' => 'Urut: terbaru dibuat'] as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="filter-panel-foot">
                @if ($filter->isActive())
                    <a href="{{ $action }}?tampilan={{ $view }}" class="btn btn-sm btn-ghost">Reset</a>
                @endif
                <button type="submit" class="btn btn-sm btn-primary">Terapkan</button>
            </div>
        </div>
    </div>

    @if ($filter->isActive())
        <a href="{{ $action }}?tampilan={{ $view }}" class="btn btn-sm btn-ghost filter-reset">Reset</a>
    @endif
</form>
