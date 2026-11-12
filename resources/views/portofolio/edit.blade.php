@extends('layouts.app')

@section('title', 'Portofolio')

@section('content')
    <?php
        $selected = collect(old('task_ids', $portfolio->task_ids ?? []))->map(fn ($id) => (int) $id)->all();
        $skills = old('skills', implode(', ', $portfolio->skills ?? []));
        $max = \App\Models\Portfolio::MAX_TASKS;
    ?>
    <div class="page-head">
        <div>
            <h1 class="page-title">Portofolio KP</h1>
            <p class="page-sub">Halaman publik berisi pengalaman KP dan hasil kerja pilihanmu, terverifikasi SIMAGTI. Bagikan di CV atau LinkedIn.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('portofolio.update') }}" class="layout portfolio-form">
        @csrf
        @method('PATCH')
        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Isi portofolio</h2></div>
                <div class="card-body stack">
                    <div class="field">
                        <label class="label" for="pf-headline">Judul singkat</label>
                        <input id="pf-headline" name="headline" class="input" maxlength="120" value="{{ old('headline', $portfolio->headline) }}" placeholder="Contoh: {{ $internship->position ?? 'Backend Developer Intern' }} yang suka membangun API">
                        @error('headline')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label class="label" for="pf-about">Tentang KP-mu</label>
                        <textarea id="pf-about" name="about" class="textarea" maxlength="1000" rows="4" placeholder="Ceritakan singkat peran, proyek, dan hal yang kamu pelajari.">{{ old('about', $portfolio->about) }}</textarea>
                        @error('about')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label class="label" for="pf-skills">Keahlian</label>
                        <input id="pf-skills" name="skills" class="input" maxlength="300" value="{{ $skills }}" placeholder="Laravel, MySQL, REST API, Git">
                        <p class="hint">Pisahkan dengan koma, maksimal {{ \App\Models\Portfolio::MAX_SKILLS }}.</p>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Hasil kerja pilihan <span class="dash-count" data-pf-count>{{ count($selected) }}/{{ $max }}</span></h2>
                </div>
                @if ($tasks->isEmpty())
                    <div class="empty"><strong>Belum ada task selesai</strong> Task yang sudah selesai bisa dipilih untuk ditampilkan di portofolio.</div>
                @else
                    <ul class="pf-tasks" data-pf-tasks data-max="{{ $max }}">
                        @foreach ($tasks as $task)
                            <li>
                                <label class="pf-task">
                                    <input type="checkbox" name="task_ids[]" value="{{ $task->id }}" @checked(in_array($task->id, $selected, true))>
                                    <span class="min0">
                                        <b>{{ $task->title }}</b>
                                        <small>{{ $task->key() }}@if ($task->plan) · {{ $task->plan->title }}@endif @if ($task->completed_at) · selesai {{ $task->completed_at->locale('id')->translatedFormat('j M Y') }}@endif</small>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                    @error('task_ids')<p class="field-error pf-error">{{ $message }}</p>@enderror
                @endif
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
                    <label @class(['pf-switch', 'is-disabled' => ! $hasGrade])>
                        <input type="hidden" name="show_grade" value="0">
                        <input type="checkbox" name="show_grade" value="1" @checked(old('show_grade', $portfolio->show_grade)) @disabled(! $hasGrade)>
                        <span class="pf-switch-ui" aria-hidden="true"></span>
                        <span><b>Tampilkan nilai akhir</b><small>{{ $hasGrade ? 'Huruf mutu dan nilai akhir ikut tampil.' : 'Tersedia setelah penilaian divalidasi.' }}</small></span>
                    </label>

                    @if ($portfolio->is_public)
                        <div class="pf-link">
                            <div class="pf-qr" role="img" aria-label="Kode QR portofolio">{!! $qr !!}</div>
                            <div class="min0">
                                <input class="input input-sm" value="{{ $portfolio->publicUrl() }}" readonly aria-label="Tautan portofolio" data-pf-url>
                                <div class="row-actions pf-link-actions">
                                    <button type="button" class="btn btn-sm" data-pf-copy>Salin tautan</button>
                                    <a href="{{ $portfolio->publicUrl() }}" target="_blank" rel="noopener" class="btn btn-sm">Lihat</a>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="pf-off">Tautan publik nonaktif. Nyalakan lalu simpan untuk mendapatkan tautan dan kode QR.</p>
                    @endif

                    <button type="submit" class="btn btn-primary pf-save">Simpan portofolio</button>
                </div>
            </section>
        </div>
    </form>

    <script>
        (function () {
            var list = document.querySelector('[data-pf-tasks]');
            var count = document.querySelector('[data-pf-count]');
            if (list) {
                var max = parseInt(list.getAttribute('data-max'), 10);
                var boxes = [].slice.call(list.querySelectorAll('input[type=checkbox]'));
                var sync = function () {
                    var n = boxes.filter(function (b) { return b.checked; }).length;
                    count.textContent = n + '/' + max;
                    boxes.forEach(function (b) { b.disabled = !b.checked && n >= max; b.closest('.pf-task').classList.toggle('is-disabled', b.disabled); });
                };
                boxes.forEach(function (b) { b.addEventListener('change', sync); });
                sync();
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
