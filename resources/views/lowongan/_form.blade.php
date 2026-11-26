{{-- Formulir lowongan (buat/ubah). Variabel: id, action, method, posting (opsional), bag, companies --}}
<?php $e = $errors->getBag($bag); ?>
<dialog id="{{ $id }}" class="dialog dialog-wide" @if ($e->any()) data-autoopen @endif>
    <form method="POST" action="{{ $action }}" class="dialog-body">
        @csrf
        @if ($method !== 'POST') @method($method) @endif
        <div class="dialog-head">
            <div>
                <h2 class="card-title">{{ $posting ? 'Ubah lowongan' : 'Lowongan baru' }}</h2>
                <p class="card-sub">Terlihat mahasiswa setelah dibuka</p>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
        </div>
        <div class="offer-form">
            <div class="form-grid">
                <div>
                    <label class="label" for="{{ $id }}-co">Perusahaan</label>
                    <select id="{{ $id }}-co" name="perusahaan" class="select" required>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}" @selected((int) old('perusahaan', $posting?->company_id) === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @if ($e->has('perusahaan'))<p class="field-error">{{ $e->first('perusahaan') }}</p>@endif
                </div>
                <div>
                    <label class="label" for="{{ $id }}-title">Posisi magang</label>
                    <input id="{{ $id }}-title" type="text" name="judul" class="input" maxlength="150" required placeholder="Magang Backend Developer" value="{{ old('judul', $posting?->title) }}">
                    @if ($e->has('judul'))<p class="field-error">{{ $e->first('judul') }}</p>@endif
                </div>
                <div class="span-all">
                    <label class="label" for="{{ $id }}-desc">Deskripsi pekerjaan</label>
                    <textarea id="{{ $id }}-desc" name="deskripsi" class="textarea" rows="4" maxlength="3000" required>{{ old('deskripsi', $posting?->description) }}</textarea>
                    @if ($e->has('deskripsi'))<p class="field-error">{{ $e->first('deskripsi') }}</p>@endif
                </div>
                <div class="span-all">
                    <label class="label" for="{{ $id }}-req">Syarat <span class="muted">(opsional)</span></label>
                    <textarea id="{{ $id }}-req" name="syarat" class="textarea" rows="2" maxlength="2000">{{ old('syarat', $posting?->requirements) }}</textarea>
                </div>
                <div>
                    <label class="label" for="{{ $id }}-loc">Lokasi <span class="muted">(opsional)</span></label>
                    <input id="{{ $id }}-loc" type="text" name="lokasi" class="input" maxlength="150" value="{{ old('lokasi', $posting?->location) }}">
                </div>
                <div>
                    <label class="label" for="{{ $id }}-quota">Kuota <span class="muted">(opsional)</span></label>
                    <input id="{{ $id }}-quota" type="number" name="kuota" class="input" min="1" max="100" value="{{ old('kuota', $posting?->quota) }}">
                </div>
                <div>
                    <label class="label" for="{{ $id }}-due">Batas pendaftaran <span class="muted">(opsional)</span></label>
                    <input id="{{ $id }}-due" type="date" name="batas" class="input" min="{{ today()->toDateString() }}" value="{{ old('batas', $posting?->deadline?->toDateString()) }}">
                    @if ($e->has('batas'))<p class="field-error">{{ $e->first('batas') }}</p>@endif
                </div>
                <div>
                    <label class="label" for="{{ $id }}-skill">Keahlian <span class="muted">(pisahkan dengan koma)</span></label>
                    <input id="{{ $id }}-skill" type="text" name="keahlian" class="input" maxlength="300" placeholder="Laravel, MySQL" value="{{ old('keahlian', $posting?->skills->pluck('name')->join(', ')) }}">
                </div>
            </div>
        </div>
        <div class="dialog-foot row-actions">
            <button type="button" class="btn" data-close>Batal</button>
            @if ($posting)
                <button type="submit" class="btn btn-primary">Simpan</button>
            @else
                <button type="submit" class="btn">Simpan draf</button>
                <button type="submit" name="buka" value="1" class="btn btn-primary">{{ auth()->user()->role === 'tu' ? 'Simpan dan buka' : 'Ajukan ke TU' }}</button>
            @endif
        </div>
    </form>
</dialog>
