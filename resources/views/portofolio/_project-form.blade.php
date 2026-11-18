{{-- Formulir proyek (tambah/ubah). Variabel: id, action, project (opsional), bag --}}
<?php
    $e = $errors->getBag($bag);
    $v = fn (string $key, $default = null) => $e->any() ? old($key, $default) : $default;
?>
<dialog id="{{ $id }}" class="dialog dialog-wide" @if ($e->any()) data-autoopen @endif>
    <form method="POST" action="{{ $action }}" class="dialog-body" enctype="multipart/form-data">
        @csrf
        <div class="dialog-head">
            <div>
                <h2 class="card-title">{{ $project ? 'Ubah proyek' : 'Tambah proyek' }}</h2>
                <p class="card-sub">Proyek yang pernah kamu kerjakan, di luar task magang</p>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
        </div>
        <div class="form-grid pf-form">
            <div class="span-all">
                <label class="label" for="{{ $id }}-title">Nama proyek</label>
                <input id="{{ $id }}-title" name="title" class="input" maxlength="120" required value="{{ $v('title', $project?->title) }}" placeholder="Aplikasi pemesanan tiket bioskop">
                @if ($e->has('title'))<p class="field-error">{{ $e->first('title') }}</p>@endif
            </div>
            <div>
                <label class="label" for="{{ $id }}-role">Peran</label>
                <input id="{{ $id }}-role" name="role" class="input" maxlength="80" value="{{ $v('role', $project?->role) }}" placeholder="Backend developer">
            </div>
            <div>
                <label class="label" for="{{ $id }}-year">Tahun</label>
                <input id="{{ $id }}-year" name="year" type="number" class="input" min="2000" max="{{ now()->year + 1 }}" value="{{ $v('year', $project?->year) }}" placeholder="{{ now()->year }}">
                @if ($e->has('year'))<p class="field-error">{{ $e->first('year') }}</p>@endif
            </div>
            <div class="span-all">
                <label class="label" for="{{ $id }}-desc">Deskripsi</label>
                <textarea id="{{ $id }}-desc" name="description" class="textarea" rows="3" maxlength="1000" placeholder="Masalah yang diselesaikan dan hasilnya.">{{ $v('description', $project?->description) }}</textarea>
            </div>
            <div class="span-all">
                <label class="label" for="{{ $id }}-tech">Teknologi</label>
                <input id="{{ $id }}-tech" name="tech" class="input" maxlength="200" value="{{ $v('tech', $project?->tech) }}" placeholder="Laravel, MySQL, Tailwind">
                <p class="hint">Pisahkan dengan koma.</p>
            </div>
            @include('portofolio._links-field', ['id' => $id, 'e' => $e, 'links' => $e->any() ? array_values(array_filter((array) old('links', []))) : array_column($project?->linkList() ?? [], 'url'), 'hint' => 'Repo, demo, desain Figma, video, dokumen, atau tautan lain tentang proyek ini.'])
            <div class="span-all">
                <label class="label" for="{{ $id }}-img">Gambar pratinjau</label>
                <input id="{{ $id }}-img" name="image" type="file" class="input" accept="image/jpeg,image/png,image/webp">
                <p class="hint">JPG, PNG, atau WEBP, paling besar 3 MB.</p>
                @if ($e->has('image'))<p class="field-error">{{ $e->first('image') }}</p>@endif
                @if ($project?->image)
                    <label class="check pf-remove"><input type="checkbox" name="remove_image" value="1"> Hapus gambar saat ini</label>
                @endif
            </div>
        </div>
        <div class="dialog-foot row-actions">
            <button type="button" class="btn" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">{{ $project ? 'Simpan proyek' : 'Tambah proyek' }}</button>
        </div>
    </form>
</dialog>
