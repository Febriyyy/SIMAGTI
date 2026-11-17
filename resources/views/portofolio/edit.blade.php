@extends('layouts.app')

@section('title', 'Portofolio')

@section('content')
    <?php
        $interests = old('interests', $portfolio->interests ?? []);
        $skillRows = old('skill_name')
            ? collect(old('skill_name'))->map(fn ($n, $i) => ['name' => $n, 'level' => old('skill_level')[$i] ?? 'menengah'])->all()
            : $skills->map(fn ($s) => ['name' => $s->name, 'level' => $s->pivot->level])->all();
        $place = collect([$academic['program'], $academic['university']])->filter()->implode(', ');
    ?>

    <div class="page-head">
        <div>
            <h1 class="page-title">Portofolio</h1>
            <p class="page-sub">Tunjukkan proyek, sertifikat, dan skill kamu kepada perusahaan, bahkan sebelum mendapat tempat magang.</p>
        </div>
        <a href="{{ route('portofolio.lihat', $user) }}" class="btn" target="_blank" rel="noopener"><x-ikon name="arrow-up-right" :size="18" /> Lihat tampilan</a>
    </div>

    <form method="POST" action="{{ route('portofolio.update') }}" class="layout portfolio-form">
        @csrf
        @method('PATCH')

        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Tentang saya</h2></div>
                <div class="card-body stack">
                    <div class="pf-identity">
                        <x-avatar :user="$user" />
                        <div class="min0">
                            <b>{{ $user->name }}</b>
                            <span>{{ $place ?: 'Program studi belum diatur admin' }}@if ($academic['entry_year']), angkatan {{ $academic['entry_year'] }}@endif</span>
                        </div>
                    </div>
                    <div>
                        <label class="label" for="pf-headline">Judul singkat</label>
                        <input id="pf-headline" name="headline" class="input" maxlength="120" value="{{ old('headline', $portfolio->headline) }}" placeholder="Mahasiswa Informatika yang suka membangun API">
                        @error('headline')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="pf-about">Deskripsi diri</label>
                        <textarea id="pf-about" name="about" class="textarea" maxlength="1000" rows="4" placeholder="Ceritakan singkat dirimu, hal yang kamu kuasai, dan pekerjaan yang kamu cari.">{{ old('about', $portfolio->about) }}</textarea>
                        @error('about')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="pf-gpa">
                        <div>
                            <label class="label" for="pf-gpa">IPK</label>
                            <input id="pf-gpa" name="gpa" type="number" class="input" min="0" max="4" step="0.01" value="{{ old('gpa', $gpa !== null ? number_format((float) $gpa, 2, '.', '') : '') }}" placeholder="3,50">
                            @error('gpa')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <label class="pf-switch">
                            <input type="hidden" name="show_gpa" value="0">
                            <input type="checkbox" name="show_gpa" value="1" @checked(old('show_gpa', $portfolio->show_gpa))>
                            <span class="pf-switch-ui" aria-hidden="true"></span>
                            <span><b>Tampilkan IPK</b><small>Bila mati, IPK hanya tersimpan untuk kamu.</small></span>
                        </label>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Minat</h2>
                    <span class="muted" data-pf-interest-count>{{ count($interests) }}/{{ \App\Models\Portfolio::MAX_INTERESTS }}</span>
                </div>
                <div class="card-body">
                    <div class="pf-interests" data-pf-interests data-max="{{ \App\Models\Portfolio::MAX_INTERESTS }}">
                        @foreach (\App\Models\Portfolio::INTERESTS as $interest)
                            <label class="pf-interest">
                                <input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, $interests, true))>
                                <span>{{ $interest }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('interests')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Skill</h2>
                    <button type="button" class="btn btn-sm" data-pf-skill-add><x-ikon name="plus" :size="16" /> Tambah skill</button>
                </div>
                <div class="card-body">
                    <datalist id="pf-skill-options">@foreach ($skillOptions as $opt)<option value="{{ $opt }}">@endforeach</datalist>
                    <ul class="pf-skills" data-pf-skills data-max="{{ \App\Models\Portfolio::MAX_SKILLS }}">
                        @foreach ($skillRows as $row)
                            <li class="pf-skill">
                                <input name="skill_name[]" class="input" list="pf-skill-options" maxlength="80" value="{{ $row['name'] }}" aria-label="Nama skill" placeholder="Laravel">
                                <select name="skill_level[]" class="select" aria-label="Tingkat">
                                    @foreach (\App\Models\Portfolio::LEVELS as $key => $label)<option value="{{ $key }}" @selected($row['level'] === $key)>{{ $label }}</option>@endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-pf-skill-remove aria-label="Hapus skill">×</button>
                            </li>
                        @endforeach
                    </ul>
                    <p class="pf-empty-line" data-pf-skill-empty @if (count($skillRows)) hidden @endif>Belum ada skill. Tambahkan bahasa pemrograman, tools, atau keahlian lain yang kamu kuasai.</p>
                    @error('skill_name')<p class="field-error">{{ $message }}</p>@enderror
                    <template data-pf-skill-template>
                        <li class="pf-skill">
                            <input name="skill_name[]" class="input" list="pf-skill-options" maxlength="80" aria-label="Nama skill" placeholder="Laravel">
                            <select name="skill_level[]" class="select" aria-label="Tingkat">
                                @foreach (\App\Models\Portfolio::LEVELS as $key => $label)<option value="{{ $key }}" @selected($key === 'menengah')>{{ $label }}</option>@endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-pf-skill-remove aria-label="Hapus skill">×</button>
                        </li>
                    </template>
                </div>
            </section>
        </div>

        <div class="col">
            <section class="card pf-share">
                <div class="card-head"><h2 class="card-title">Bagikan</h2></div>
                <div class="card-body stack">
                    <label class="pf-switch">
                        <input type="hidden" name="is_public" value="0">
                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $portfolio->is_public))>
                        <span class="pf-switch-ui" aria-hidden="true"></span>
                        <span><b>Tautan publik</b><small>Siapa pun dengan tautan bisa melihat. Bisa dimatikan kapan saja.</small></span>
                    </label>

                    @if ($portfolio->is_public)
                        <div class="pf-link">
                            <div class="pf-qr" role="img" aria-label="Kode QR portofolio">{!! $qr !!}</div>
                            <div class="min0">
                                <input class="input input-sm" value="{{ $portfolio->publicUrl() }}" readonly aria-label="Tautan portofolio" data-pf-url>
                                <div class="row-actions pf-link-actions">
                                    <button type="button" class="btn btn-sm" data-pf-copy>Salin tautan</button>
                                    <a href="{{ $portfolio->publicUrl() }}" target="_blank" rel="noopener" class="btn btn-sm">Buka</a>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="pf-off">Nyalakan lalu simpan untuk mendapatkan tautan dan kode QR.</p>
                    @endif
                    <p class="pf-note">Kampus, dosen pembimbing, dan Company Supervisor perusahaan yang kamu tuju tetap bisa melihat portofolio ini dari SIMAGTI.</p>

                    <button type="submit" class="btn btn-primary pf-save">Simpan portofolio</button>
                </div>
            </section>

            <section class="card">
                <div class="card-head"><h2 class="card-title">Pengalaman magang</h2></div>
                <div class="card-body">
                    @if ($internship)
                        <div class="pf-exp">
                            <x-org :name="$internship->placeName()" />
                            <div class="min0">
                                <b>{{ $internship->position ?? 'Kerja Praktik' }}</b>
                                <span>{{ $internship->placeName() }}</span>
                                <small>{{ $internship->periodLabel() }}</small>
                                @if ($internship->supervisor)<small>Dibimbing {{ $internship->supervisor->name }}</small>@endif
                            </div>
                        </div>
                    @else
                        <p class="pf-empty-line">Muncul otomatis setelah kamu mendapat tempat magang.</p>
                    @endif
                </div>
            </section>
        </div>
    </form>

    <section class="card pf-section" id="proyek">
        <div class="card-head">
            <h2 class="card-title">Proyek <span class="dash-count">{{ $portfolio->projects->count() }}</span></h2>
            @if ($portfolio->projects->count() < \App\Models\Portfolio::MAX_PROJECTS)
                <button type="button" class="btn btn-sm btn-primary" data-open="pf-project-new"><x-ikon name="plus" :size="16" /> Tambah proyek</button>
            @endif
        </div>
        @if ($portfolio->projects->isEmpty())
            <div class="empty"><strong>Belum ada proyek</strong> Tugas kuliah, proyek pribadi, lomba, atau pekerjaan lepas yang pernah kamu buat.</div>
        @else
            <ul class="pf-projects">
                @foreach ($portfolio->projects as $project)
                    <li class="pf-project">
                        <div @class(['pf-project-thumb', 'is-empty' => ! $project->image])>
                            @if ($project->image)
                                <img src="{{ route('portofolio.berkas', $project->image) }}" alt="" loading="lazy">
                            @else
                                <x-ikon name="project" :size="26" />
                            @endif
                        </div>
                        <div class="pf-project-body">
                            <b>{{ $project->title }}</b>
                            <small>{{ collect([$project->role, $project->year])->filter()->implode(', ') ?: 'Peran dan tahun belum diisi' }}</small>
                            @if ($project->techList())
                                <ul class="pf-tags">@foreach ($project->techList() as $t)<li>{{ $t }}</li>@endforeach</ul>
                            @endif
                        </div>
                        <div class="pf-item-actions">
                            <button type="button" class="btn btn-sm btn-ghost" data-open="pf-project-{{ $project->id }}"><x-ikon name="edit" :size="16" /> Ubah</button>
                            <form method="POST" action="{{ route('portofolio.proyek.destroy', $project) }}" onsubmit="return confirm('Hapus proyek {{ addslashes($project->title) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost pf-danger">Hapus</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card pf-section" id="sertifikat">
        <div class="card-head">
            <h2 class="card-title">Sertifikat pelatihan <span class="dash-count">{{ $portfolio->certificates->count() }}</span></h2>
            @if ($portfolio->certificates->count() < \App\Models\Portfolio::MAX_CERTIFICATES)
                <button type="button" class="btn btn-sm btn-primary" data-open="pf-cert-new"><x-ikon name="plus" :size="16" /> Tambah sertifikat</button>
            @endif
        </div>
        @if ($portfolio->certificates->isEmpty())
            <div class="empty"><strong>Belum ada sertifikat</strong> Unggah sertifikat bootcamp, kursus, atau pelatihan yang pernah kamu ikuti.</div>
        @else
            <ul class="pf-certs">
                @foreach ($portfolio->certificates as $cert)
                    <li class="pf-cert">
                        <span class="pf-cert-icon" aria-hidden="true"><x-ikon name="award" :size="20" /></span>
                        <div class="min0">
                            <b>{{ $cert->name }}</b>
                            <small>{{ $cert->issuer }}@if ($cert->issued_on), {{ $cert->issued_on->locale('id')->translatedFormat('F Y') }}@endif</small>
                        </div>
                        <div class="pf-item-actions">
                            @if ($cert->file)<a href="{{ route('portofolio.berkas', $cert->file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">Berkas</a>@endif
                            <button type="button" class="btn btn-sm btn-ghost" data-open="pf-cert-{{ $cert->id }}"><x-ikon name="edit" :size="16" /> Ubah</button>
                            <form method="POST" action="{{ route('portofolio.sertifikat.destroy', $cert) }}" onsubmit="return confirm('Hapus sertifikat {{ addslashes($cert->name) }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost pf-danger">Hapus</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @include('portofolio._project-form', ['id' => 'pf-project-new', 'action' => route('portofolio.proyek.store'), 'project' => null, 'bag' => 'project'])
    @foreach ($portfolio->projects as $project)
        @include('portofolio._project-form', ['id' => 'pf-project-'.$project->id, 'action' => route('portofolio.proyek.update', $project), 'project' => $project, 'bag' => 'project-'.$project->id])
    @endforeach
    @include('portofolio._certificate-form', ['id' => 'pf-cert-new', 'action' => route('portofolio.sertifikat.store'), 'certificate' => null, 'bag' => 'cert'])
    @foreach ($portfolio->certificates as $cert)
        @include('portofolio._certificate-form', ['id' => 'pf-cert-'.$cert->id, 'action' => route('portofolio.sertifikat.update', $cert), 'certificate' => $cert, 'bag' => 'cert-'.$cert->id])
    @endforeach

    <script>
        (function () {
            // Minat: paling banyak N pilihan
            var box = document.querySelector('[data-pf-interests]');
            var counter = document.querySelector('[data-pf-interest-count]');
            if (box) {
                var max = parseInt(box.getAttribute('data-max'), 10);
                var checks = [].slice.call(box.querySelectorAll('input'));
                var sync = function () {
                    var n = checks.filter(function (c) { return c.checked; }).length;
                    counter.textContent = n + '/' + max;
                    checks.forEach(function (c) { c.disabled = !c.checked && n >= max; c.closest('.pf-interest').classList.toggle('is-disabled', c.disabled); });
                };
                checks.forEach(function (c) { c.addEventListener('change', sync); });
                sync();
            }

            // Skill: tambah dan hapus baris
            var list = document.querySelector('[data-pf-skills]');
            var tpl = document.querySelector('[data-pf-skill-template]');
            var empty = document.querySelector('[data-pf-skill-empty]');
            var add = document.querySelector('[data-pf-skill-add]');
            if (list && tpl && add) {
                var limit = parseInt(list.getAttribute('data-max'), 10);
                var refresh = function () {
                    var n = list.children.length;
                    empty.hidden = n > 0;
                    add.disabled = n >= limit;
                };
                add.addEventListener('click', function () {
                    if (list.children.length >= limit) return;
                    list.appendChild(tpl.content.cloneNode(true));
                    list.lastElementChild.querySelector('input').focus();
                    refresh();
                });
                list.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-pf-skill-remove]');
                    if (!btn) return;
                    btn.closest('.pf-skill').remove();
                    refresh();
                });
                refresh();
            }

            var copy = document.querySelector('[data-pf-copy]');
            if (copy) copy.addEventListener('click', function () {
                var input = document.querySelector('[data-pf-url]');
                var done = function () { copy.textContent = 'Tersalin'; setTimeout(function () { copy.textContent = 'Salin tautan'; }, 1800); };
                if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(input.value).then(done);
                else { input.select(); try { document.execCommand('copy'); done(); } catch (e) {} }
            });
        })();
    </script>
@endsection
