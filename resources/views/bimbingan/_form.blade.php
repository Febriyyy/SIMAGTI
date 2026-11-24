{{-- Formulir catat bimbingan. Dipakai di kolom kanan (desktop) dan di dialog (HP); $prefix membedakan id isian. --}}
@php($prefix = $prefix ?? 'g')
<form method="POST" action="{{ route('bimbingan.store') }}" class="stack">
    @csrf
    @if ($prefix !== 'g')<input type="hidden" name="_dlg" value="1">@endif
    <div class="form-grid gform-row">
        <div class="field">
            <label class="label" for="{{ $prefix }}-held_on">Tanggal</label>
            <input id="{{ $prefix }}-held_on" type="date" name="held_on" class="input" value="{{ old('held_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
        </div>
        <div class="field">
            <label class="label" for="{{ $prefix }}-mode">Cara</label>
            <select id="{{ $prefix }}-mode" name="mode" class="select">
                @foreach (\App\Models\GuidanceSession::MODES as $k => $v)
                    <option value="{{ $k }}" @selected(old('mode') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="field">
        <label class="label" for="{{ $prefix }}-topic">Topik</label>
        <input id="{{ $prefix }}-topic" type="text" name="topic" class="input" maxlength="200" value="{{ old('topic') }}" placeholder="Contoh: Pembahasan kerangka laporan" required>
    </div>
    <div class="field">
        <label class="label" for="{{ $prefix }}-notes">Catatan / hasil bimbingan <span class="label-opt">(opsional)</span></label>
        <textarea id="{{ $prefix }}-notes" name="notes" class="textarea" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
    </div>
    <div class="form-actions">
        <p class="hint">Dikonfirmasi dosen pembimbing.</p>
        <div class="row-actions"><button type="submit" class="btn btn-primary"><x-ikon name="plus" :size="18" /> Simpan</button></div>
    </div>
</form>
