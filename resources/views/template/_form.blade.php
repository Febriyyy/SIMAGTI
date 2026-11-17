{{-- Formulir template task (buat/ubah). Variabel: id, action, method, template (opsional), bag --}}
<?php
    $e = $errors->getBag($bag);
    $rows = old('items', $template?->items->map(fn ($i) => ['minggu' => $i->week_number, 'hari' => $i->day, 'judul' => $i->title])->values()->all() ?? []);
    $count = max(count($rows) + 3, 6);
?>
<dialog id="{{ $id }}" class="dialog dialog-wide" @if ($e->any()) data-autoopen @endif>
    <form method="POST" action="{{ $action }}" class="dialog-body" data-template-form>
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div class="dialog-head">
            <div>
                <h2 class="card-title">{{ $template ? 'Ubah template' : 'Template baru' }}</h2>
                <p class="card-sub">Minggu 1 = minggu KP mahasiswa saat template diterapkan</p>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
        </div>

        <div class="form-grid">
            <div>
                <label class="label" for="{{ $id }}-name">Nama template</label>
                <input id="{{ $id }}-name" type="text" name="nama" class="input" maxlength="120" required placeholder="Onboarding minggu pertama" value="{{ old('nama', $template?->name) }}">
                @if ($e->has('nama'))<p class="field-error">{{ $e->first('nama') }}</p>@endif
            </div>
            <div>
                <label class="label" for="{{ $id }}-desc">Keterangan <span class="muted">(opsional)</span></label>
                <input id="{{ $id }}-desc" type="text" name="keterangan" class="input" maxlength="300" value="{{ old('keterangan', $template?->description) }}">
            </div>
        </div>

        <div class="tpl-rows" data-rows>
            <div class="tpl-row tpl-row-head" aria-hidden="true"><span>Minggu</span><span>Hari</span><span>Task</span></div>
            @for ($n = 0; $n < $count; $n++)
                <?php $r = $rows[$n] ?? []; ?>
                <div class="tpl-row" data-row>
                    <input type="number" name="items[{{ $n }}][minggu]" class="input" min="1" max="52" value="{{ $r['minggu'] ?? 1 }}" aria-label="Minggu ke, baris {{ $n + 1 }}">
                    <select name="items[{{ $n }}][hari]" class="select" aria-label="Hari, baris {{ $n + 1 }}">
                        @foreach (\App\Models\WeeklyTask::DAYS as $k => $label)
                            <option value="{{ $k }}" @selected(($r['hari'] ?? 'senin') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="items[{{ $n }}][judul]" class="input" maxlength="150" placeholder="{{ $n === 0 ? 'Mis. Perkenalan tim dan alat kerja' : '' }}" value="{{ $r['judul'] ?? '' }}" aria-label="Judul task, baris {{ $n + 1 }}">
                </div>
            @endfor
        </div>
        @foreach (['items', 'items.*'] as $k)
            @if ($e->has($k))<p class="field-error">{{ $e->first($k) }}</p>@endif
        @endforeach
        <button type="button" class="btn btn-sm btn-ghost tpl-add" data-add-row><x-ikon name="plus" :size="16" /> Tambah baris</button>

        <div class="dialog-foot row-actions">
            <button type="button" class="btn" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan template</button>
        </div>
    </form>
</dialog>
