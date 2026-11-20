@extends('layouts.app')

@section('title', 'Data Sertifikat')

{{--
    Company Supervisor: logo perusahaan, nama dan tanda tangan ketua divisi untuk sertifikat KP.
    Kiri: formulir dengan area unggah yang bisa diklik. Kanan: pratinjau potongan sertifikat dan kelengkapan.
    Nama dan tanda tangan yang baru dipilih langsung tampil di pratinjau sebelum disimpan.
    Variabel: companies, divisions, signatures
--}}
@section('content')
    <?php
        $items = collect();
        foreach ($companies as $c) {
            $items->push(['Logo '.$c->name, (bool) $c->logo_file_id]);
        }
        foreach ($divisions as $d) {
            $items->push(['Nama ketua divisi '.$d->name, filled($d->head_name)]);
            $items->push(['Tanda tangan ketua divisi '.$d->name, (bool) ($signatures[$d->id] ?? null)]);
        }
        $done = $items->where(1, true)->count();
    ?>
    <div class="page-head">
        <div>
            <h1 class="page-title">Data Sertifikat</h1>
            <p class="page-sub">Diisi sekali, dipakai di sertifikat KP semua mahasiswa magang Anda.</p>
        </div>
    </div>

    <div class="layout ds">
        <div class="col">
            @foreach ($companies as $company)
                <?php $bag = $errors->getBag('logo'.$company->id); ?>
                <section class="card ds-card" id="perusahaan-{{ $company->id }}">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Logo perusahaan</h2>
                            <p class="card-sub">{{ $company->name }}</p>
                        </div>
                        <x-chip :tone="$company->logo_file_id ? 'green' : 'amber'">{{ $company->logo_file_id ? 'Terpasang' : 'Belum ada' }}</x-chip>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sertifikat.data.logo', $company) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <label @class(['ds-drop', 'has-file' => $company->logo_file_id])>
                                @if ($company->logo_file_id)
                                    <img src="{{ route('logo.perusahaan', $company) }}?v={{ $company->logo_file_id }}" alt="Logo {{ $company->name }}">
                                    <span class="ds-drop-hover"><x-ikon name="upload" :size="18" /> Ganti logo</span>
                                @else
                                    <span class="ds-drop-icon"><x-ikon name="image" :size="22" /></span>
                                    <b>Klik untuk unggah logo</b>
                                    <small>PNG, JPG, atau WebP, maks. 1 MB. Latar transparan paling rapi.</small>
                                @endif
                                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah logo {{ $company->name }}">
                            </label>
                            @if ($bag->has('logo'))<p class="field-error">{{ $bag->first('logo') }}</p>@endif
                        </form>
                        @if ($company->logo_file_id)
                            <form method="POST" action="{{ route('sertifikat.data.logo.destroy', $company) }}" class="ds-remove" onsubmit="return confirm('Hapus logo {{ $company->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost"><x-ikon name="delete" :size="16" /> Hapus logo</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endforeach

            @forelse ($divisions as $division)
                <?php $bag = $errors->getBag('divisi'.$division->id); $sig = $signatures[$division->id] ?? null; ?>
                <section class="card ds-card" id="divisi-{{ $division->id }}">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Ketua Divisi {{ $division->name }}</h2>
                            <p class="card-sub">{{ $division->company?->name }}</p>
                        </div>
                        <x-chip :tone="$division->head_name && $sig ? 'green' : 'amber'">{{ $division->head_name && $sig ? 'Lengkap' : 'Belum lengkap' }}</x-chip>
                    </div>
                    <form method="POST" action="{{ route('sertifikat.data.divisi', $division) }}" enctype="multipart/form-data" class="card-body ds-form" data-ds-division="{{ $division->id }}">
                        @csrf
                        @method('PUT')
                        <div class="field">
                            <label class="label" for="dh-name-{{ $division->id }}">Nama ketua divisi</label>
                            <input id="dh-name-{{ $division->id }}" type="text" name="head_name" class="input" maxlength="150" required placeholder="Nama lengkap beserta gelar" value="{{ old('head_name', $division->head_name) }}" data-ds-name>
                            @if ($bag->has('head_name'))<p class="field-error">{{ $bag->first('head_name') }}</p>@endif
                        </div>

                        <div class="field">
                            <span class="label">Tanda tangan</span>
                            {{-- Area seperti kertas: klik untuk memilih gambar, pratinjau langsung tampil --}}
                            <label @class(['ds-sign', 'has-file' => $sig])>
                                <img src="{{ $sig }}" alt="Tanda tangan ketua divisi {{ $division->name }}" data-ds-sign-img @if (! $sig) hidden @endif>
                                <span class="ds-sign-empty" @if ($sig) hidden @endif>
                                    <x-ikon name="edit" :size="20" />
                                    <b>Klik untuk unggah tanda tangan</b>
                                    <small>Foto atau pindai tanda tangan di kertas putih</small>
                                </span>
                                <span class="ds-sign-line" aria-hidden="true"><i>×</i></span>
                                <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" class="sr-only" data-ds-sign aria-label="Berkas tanda tangan ketua divisi {{ $division->name }}">
                            </label>
                            <div class="ds-sign-meta">
                                <small>PNG berlatar transparan paling rapi, maks. 1 MB.</small>
                                @if ($sig)
                                    <label class="check"><input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan</label>
                                @endif
                            </div>
                            @if ($bag->has('signature'))<p class="field-error">{{ $bag->first('signature') }}</p>@endif
                        </div>

                        <div class="ds-actions">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </section>
            @empty
                <section class="card">
                    <div class="empty">
                        <strong>Belum ada divisi</strong>
                        Ketua divisi bisa diisi setelah ada mahasiswa magang yang Anda supervisi di sebuah divisi.
                    </div>
                </section>
            @endforelse
        </div>

        {{-- Pratinjau di sertifikat dan kelengkapan --}}
        <div class="col">
            <section class="card ds-preview">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Pratinjau di sertifikat</h2>
                        <p class="card-sub">{{ $done }} dari {{ $items->count() }} lengkap</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ds-paper">
                        <div class="ds-paper-logos">
                            <span class="ds-slot">Kampus</span>
                            <span class="ds-slot">Fakultas</span>
                            <i aria-hidden="true"></i>
                            @forelse ($companies as $company)
                                @if ($company->logo_file_id)
                                    <img src="{{ route('logo.perusahaan', $company) }}?v={{ $company->logo_file_id }}" alt="Logo {{ $company->name }}">
                                @else
                                    <span class="ds-slot is-mine">Logo Anda</span>
                                @endif
                            @empty
                                <span class="ds-slot is-mine">Logo Anda</span>
                            @endforelse
                        </div>
                        <p class="ds-paper-title">Sertifikat Kerja Praktik</p>
                        <p class="ds-paper-name">Nama Mahasiswa</p>
                        <div class="ds-paper-signs">
                            @foreach ($divisions->take(1) as $division)
                                <?php $sig = $signatures[$division->id] ?? null; ?>
                                <div class="ds-paper-sign is-mine">
                                    <small>Ketua Divisi {{ $division->name }}</small>
                                    <span class="ds-paper-ink">
                                        <img src="{{ $sig }}" alt="" data-ds-preview-sign="{{ $division->id }}" @if (! $sig) hidden @endif>
                                    </span>
                                    <b data-ds-preview-name="{{ $division->id }}">{{ $division->head_name ?: 'Nama ketua divisi' }}</b>
                                    <small>{{ $division->company?->name }}</small>
                                </div>
                            @endforeach
                            <div class="ds-paper-sign">
                                <small>Ketua Program Studi</small>
                                <span class="ds-paper-ink"></span>
                                <b>Diisi kaprodi</b>
                                <small>Program studi</small>
                            </div>
                        </div>
                    </div>

                    <ul class="ds-checklist">
                        @foreach ($items as [$label, $ok])
                            <li @class(['is-ok' => $ok])>
                                <span aria-hidden="true">@if ($ok)<x-ikon name="tick" :size="14" />@endif</span>
                                {{ $label }}<span class="sr-only">: {{ $ok ? 'sudah' : 'belum' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>
    </div>

    <script>
        (function () {
            // Pratinjau langsung: nama yang diketik dan tanda tangan yang dipilih sebelum disimpan
            document.querySelectorAll('[data-ds-division]').forEach(function (form) {
                var id = form.getAttribute('data-ds-division');
                var name = form.querySelector('[data-ds-name]');
                var file = form.querySelector('[data-ds-sign]');
                var img = form.querySelector('[data-ds-sign-img]');
                var empty = form.querySelector('.ds-sign-empty');
                var pvName = document.querySelector('[data-ds-preview-name="' + id + '"]');
                var pvSign = document.querySelector('[data-ds-preview-sign="' + id + '"]');
                if (name && pvName) {
                    name.addEventListener('input', function () { pvName.textContent = name.value.trim() || 'Nama ketua divisi'; });
                }
                if (file) {
                    file.addEventListener('change', function () {
                        var f = file.files && file.files[0];
                        if (!f) return;
                        var url = URL.createObjectURL(f);
                        img.src = url; img.hidden = false;
                        if (empty) empty.hidden = true;
                        img.closest('.ds-sign').classList.add('has-file');
                        if (pvSign) { pvSign.src = url; pvSign.hidden = false; }
                    });
                }
            });
        })();
    </script>
@endsection
