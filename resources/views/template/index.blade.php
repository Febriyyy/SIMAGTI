@extends('layouts.app')

@section('title', 'Template Task')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Template Task</h1>
            <p class="page-sub">Kumpulan task baku yang bisa diterapkan ke mahasiswa magang sekali klik.</p>
        </div>
        <button type="button" class="btn btn-primary" data-open="tpl-new"><x-ikon name="plus" :size="18" /> Template baru</button>
    </div>

    @if ($templates->isEmpty())
        <section class="card">
            <div class="empty">
                <strong>Belum ada template</strong>
                Buat template untuk tugas yang selalu sama, misalnya onboarding minggu pertama, lalu terapkan ke mahasiswa magang.
                <div class="row-actions" style="justify-content:center; margin-top: var(--space-4)"><button type="button" class="btn btn-primary" data-open="tpl-new">Buat template</button></div>
            </div>
        </section>
    @else
        <ul class="tpl-grid">
            @foreach ($templates as $t)
                <?php $weeks = $t->items->groupBy('week_number'); ?>
                <li class="card tpl-card">
                    <div class="tpl-card-head">
                        <span class="tpl-icon" aria-hidden="true"><x-ikon name="tasks" :size="20" /></span>
                        <div class="min0">
                            <h2 class="tpl-name">{{ $t->name }}</h2>
                            <p class="tpl-meta">{{ $t->items->count() }} task · {{ $weeks->count() }} minggu</p>
                        </div>
                    </div>
                    @if ($t->description)<p class="tpl-desc">{{ $t->description }}</p>@endif
                    <ol class="tpl-weeks">
                        @foreach ($weeks->take(2) as $week => $items)
                            <li>
                                <span class="tpl-week">Minggu {{ $week }}</span>
                                <ul class="tpl-items">
                                    @foreach ($items->take(3) as $item)
                                        <li><span class="tpl-day">{{ \App\Models\WeeklyTask::DAYS[$item->day] ?? $item->day }}</span>{{ $item->title }}</li>
                                    @endforeach
                                    @if ($items->count() > 3)<li class="tpl-more">+{{ $items->count() - 3 }} task lagi</li>@endif
                                </ul>
                            </li>
                        @endforeach
                        @if ($weeks->count() > 2)<li class="tpl-more">+{{ $weeks->count() - 2 }} minggu lagi</li>@endif
                    </ol>
                    <div class="tpl-actions">
                        <form method="POST" action="{{ route('template.destroy', $t) }}" onsubmit="return confirm('Hapus template {{ $t->name }}? Task yang sudah diterapkan tetap ada.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" aria-label="Hapus {{ $t->name }}"><x-ikon name="delete" :size="16" /></button>
                        </form>
                        <button type="button" class="btn btn-sm btn-ghost" data-open="tpl-edit-{{ $t->id }}"><x-ikon name="edit" :size="16" /> Ubah</button>
                        <button type="button" class="btn btn-sm btn-primary" data-open="tpl-apply-{{ $t->id }}">Terapkan</button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @include('template._form', ['id' => 'tpl-new', 'action' => route('template.store'), 'method' => 'POST', 'template' => null, 'bag' => 'template'])

    @foreach ($templates as $t)
        @include('template._form', ['id' => 'tpl-edit-'.$t->id, 'action' => route('template.update', $t), 'method' => 'PUT', 'template' => $t, 'bag' => 'template'.$t->id])

        <?php $ab = $errors->getBag('apply'.$t->id); ?>
        <dialog id="tpl-apply-{{ $t->id }}" class="dialog" @if ($ab->any()) data-autoopen @endif>
            <form method="POST" action="{{ route('template.terapkan', $t) }}" class="dialog-body">
                @csrf
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Terapkan "{{ $t->name }}"</h2>
                        <p class="card-sub">{{ $t->items->count() }} task dibuat di board mahasiswa yang dipilih</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                @if ($interns->isEmpty())
                    <p class="muted">Belum ada mahasiswa magang yang sedang berjalan.</p>
                @else
                    <fieldset class="tpl-pick">
                        <legend class="label">Mahasiswa</legend>
                        @foreach ($interns as $i)
                            <label class="tpl-pick-row">
                                <input type="checkbox" name="mahasiswa[]" value="{{ $i->id }}" @checked(in_array($i->id, (array) old('mahasiswa', [])))>
                                <x-avatar :user="$i->student" small />
                                <span class="min0"><b>{{ $i->student->listName() }}</b><small>Minggu ke-{{ $i->currentWeek() }} dari {{ $i->totalWeeks() }}</small></span>
                            </label>
                        @endforeach
                        @if ($ab->has('mahasiswa'))<p class="field-error">{{ $ab->first('mahasiswa') }}</p>@endif
                    </fieldset>
                @endif
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary" @disabled($interns->isEmpty())>Terapkan</button>
                </div>
            </form>
        </dialog>
    @endforeach

    <script>
        // Tambah baris task di formulir template (menyalin baris terakhir dengan indeks baru)
        document.querySelectorAll('[data-template-form]').forEach(function (form) {
            var btn = form.querySelector('[data-add-row]');
            btn && btn.addEventListener('click', function () {
                var rows = form.querySelector('[data-rows]');
                var all = rows.querySelectorAll('[data-row]');
                if (all.length >= {{ \App\Http\Controllers\TaskTemplateController::MAX_ITEMS }}) return;
                var last = all[all.length - 1], clone = last.cloneNode(true), n = all.length;
                clone.querySelectorAll('[name]').forEach(function (el) { el.name = el.name.replace(/items\[\d+\]/, 'items[' + n + ']'); if (el.type === 'text') el.value = ''; });
                rows.appendChild(clone);
                clone.querySelector('input[type=text]').focus();
            });
        });
    </script>
@endsection
