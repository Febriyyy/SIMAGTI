{{-- Isian rencana kegiatan. Variabel: $plan (null = baru), $internship, $goals, $useOld (isi ulang dari input gagal), $colorIndex (urutan untuk warna bawaan) --}}
@php($p = $plan)
@php($id = $p?->id ?? 'new')
@php($color = $p ? $p->colorKey($colorIndex ?? 0) : array_keys(\App\Models\ActivityPlan::COLORS)[($colorIndex ?? 0) % count(\App\Models\ActivityPlan::COLORS)])
@php($val = fn (string $key, $default) => ($useOld ?? false) ? old($key, $default) : $default)
<div class="form-grid">
    <div class="span-all">
        <label class="label" for="pl-title-{{ $id }}">Nama kegiatan</label>
        <input id="pl-title-{{ $id }}" type="text" name="title" class="input" maxlength="160" required value="{{ $val('title', $p?->title) }}" placeholder="Misalnya Analisis kebutuhan modul pemesanan">
    </div>
    <div>
        <label class="label" for="pl-start-{{ $id }}">Mulai</label>
        <input id="pl-start-{{ $id }}" type="date" name="start_date" class="input" required
               min="{{ $internship->start_date->toDateString() }}" max="{{ $internship->end_date->toDateString() }}"
               value="{{ $val('start_date', $p?->start_date?->toDateString() ?? now()->max($internship->start_date)->min($internship->end_date)->toDateString()) }}">
    </div>
    <div>
        <label class="label" for="pl-end-{{ $id }}">Selesai</label>
        <input id="pl-end-{{ $id }}" type="date" name="end_date" class="input" required
               min="{{ $internship->start_date->toDateString() }}" max="{{ $internship->end_date->toDateString() }}"
               value="{{ $val('end_date', $p?->end_date?->toDateString()) }}">
    </div>
    <div class="span-all span-chips">
        <span class="hint">Lama pengerjaan:</span>
        <button type="button" class="seg-chip" data-span="7">1 minggu</button>
        <button type="button" class="seg-chip" data-span="14">2 minggu</button>
        <button type="button" class="seg-chip" data-span="30">1 bulan</button>
    </div>
    <fieldset class="span-all plain-fieldset">
        <legend class="label">Warna di Gantt</legend>
        <div class="color-dots">
            @foreach (\App\Models\ActivityPlan::COLORS as $key => $name)
                <label class="color-dot" style="--c: var(--gc-{{ $key }})" title="{{ $name }}">
                    <input type="radio" name="color" value="{{ $key }}" @checked($val('color', $color) === $key) aria-label="{{ $name }}">
                    <span></span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <div class="span-all">
        <label class="label" for="pl-goal-{{ $id }}">Goal (opsional)</label>
        <select id="pl-goal-{{ $id }}" name="goal_id" class="select">
            <option value="">Tidak dikaitkan</option>
            @foreach ($goals as $goal)
                <option value="{{ $goal->id }}" @selected((int) $val('goal_id', $p?->goal_id) === $goal->id)>{{ \Illuminate\Support\Str::limit($goal->title, 60) }}</option>
            @endforeach
        </select>
    </div>
    <div class="span-all">
        <label class="label" for="pl-desc-{{ $id }}">Keterangan (opsional)</label>
        <textarea id="pl-desc-{{ $id }}" name="description" class="textarea" rows="3" maxlength="2000" placeholder="Hasil yang diharapkan dari kegiatan ini">{{ $val('description', $p?->description) }}</textarea>
    </div>
</div>
