{{--
    Dialog jadwal sidang KP (dipakai dosen pembimbing). Variabel: $i (Internship), $examiners (dosen penguji), $action (nama rute PUT jadwal)
--}}
<?php $s = $i->defenseSchedule; $bag = $errors->getBag('jadwal'.$i->id); ?>
<dialog id="dlg-sch-{{ $i->id }}" class="dialog" @if ($bag->any()) data-autoopen @endif>
    <form method="POST" action="{{ route($action, $i) }}" class="dialog-body">
        @csrf
        @method('PUT')
        <div class="dialog-head">
            <div>
                <h2 class="card-title">{{ $s ? 'Ubah jadwal sidang' : 'Jadwalkan sidang' }}</h2>
                <p class="card-sub">{{ $i->student->name }}</p>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
        </div>
        <div class="form-grid">
            <div>
                <label class="label" for="sch-date-{{ $i->id }}">Tanggal</label>
                <input id="sch-date-{{ $i->id }}" type="date" name="tanggal" class="input" required min="{{ today()->toDateString() }}" value="{{ old('tanggal', $s?->scheduled_at->toDateString()) }}">
                @if ($bag->has('tanggal'))<p class="field-error">{{ $bag->first('tanggal') }}</p>@endif
            </div>
            <div>
                <label class="label" for="sch-time-{{ $i->id }}">Jam</label>
                <input id="sch-time-{{ $i->id }}" type="time" name="jam" class="input" required value="{{ old('jam', $s?->scheduled_at->format('H:i') ?? '09:00') }}">
            </div>
            <div>
                <label class="label" for="sch-mode-{{ $i->id }}">Cara</label>
                <select id="sch-mode-{{ $i->id }}" name="cara" class="select">
                    @foreach (\App\Models\DefenseSchedule::MODES as $k => $label)
                        <option value="{{ $k }}" @selected(old('cara', $s?->mode ?? 'luring') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="sch-ex-{{ $i->id }}">Dosen penguji <span class="muted">(opsional)</span></label>
                <select id="sch-ex-{{ $i->id }}" name="penguji" class="select">
                    <option value="">Belum ditentukan</option>
                    @foreach ($examiners as $ex)
                        <option value="{{ $ex->id }}" @selected((int) old('penguji', $s?->examiner_id) === $ex->id)>{{ $ex->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="span-all">
                <label class="label" for="sch-place-{{ $i->id }}">Ruang atau tautan rapat</label>
                <input id="sch-place-{{ $i->id }}" type="text" name="tempat" class="input" maxlength="200" required placeholder="Ruang B2.04 atau https://meet.google.com/..." value="{{ old('tempat', $s?->place) }}">
                @if ($bag->has('tempat'))<p class="field-error">{{ $bag->first('tempat') }}</p>@endif
            </div>
        </div>
        <div class="dialog-foot row-actions">
            <button type="button" class="btn" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan dan beri tahu</button>
        </div>
    </form>
</dialog>
