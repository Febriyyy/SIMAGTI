@extends('layouts.app')

@section('title', 'Laporan KP')

{{--
    Laporan KP bersama mahasiswa dan dosen pembimbing. Mahasiswa mengikuti tiga langkah: tulis (draf otomatis),
    unggah dan diskusikan per bab, lalu unggah laporan akhir setelah semua bab disetujui dosen.
    Tiap bab punya utas diskusi (boleh berlampiran berkas koreksi); laporan akhir dinilai dosen pada empat aspek.
--}}
@section('content')
    <?php
        $me = auth()->user();
        $final = $internship->finalReport;
        $score = $internship->reportScore;
        $student = $internship->student;
        $approved = $chapters->where('status', 'disetujui')->count();
        $allApproved = $chapters->isNotEmpty() && $approved === $chapters->count();
        $uploaded = $chapters->filter(fn ($c) => $c->file_id)->count();
        $canUpload = $isStudent && ! $locked && in_array($internship->status, ['berjalan', 'selesai'], true);
        // Bab yang sedang dikerjakan: bab pertama yang belum disetujui (dibuka otomatis)
        $current = $chapters->first(fn ($c) => $c->status !== 'disetujui');
    ?>
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ $isStudent ? 'Laporan KP' : 'Laporan '.$student->name }}</h1>
            <p class="page-sub">
                @if ($isStudent)
                    Tulis, unggah per bab, dan diskusikan dengan {{ $internship->lecturer?->listName() ?? 'dosen pembimbing' }}.
                @else
                    {{ $student->name }} · {{ $internship->placeName() }}
                @endif
            </p>
        </div>
        @unless ($isStudent)
            <a href="{{ route('laporan.antrean') }}" class="btn btn-ghost"><x-ikon name="back" :size="16" /> Kembali</a>
        @endunless
    </div>

    @if ($locked)
        {{-- Arsip: setelah nilai sidang disahkan, semua isian ditutup --}}
        <div class="rd-archive" role="status">
            <x-ikon name="lock" :size="18" />
            <p><b>Nilai sidang sudah disahkan.</b> Laporan KP ini kini arsip dan hanya bisa dibaca.@if ($internship->defenseScore) Nilai sidang {{ \App\Support\Num::short($internship->defenseScore->score) }} ({{ $internship->defenseScore->letter_grade }}).@endif</p>
        </div>
    @endif

    @if ($isStudent && ! $locked)
        {{-- Tiga langkah laporan: yang selesai bertanda centang, langkah berikutnya disorot --}}
        <?php
            $steps = [
                ['Tulis laporan', $uploaded > 0 ? 'Bab pertama sudah diunggah' : 'Mulai dari draf otomatis berisi data KP-mu', $uploaded > 0],
                ['Unggah per bab', $approved.' dari '.$chapters->count().' bab disetujui dosen', $allApproved],
                ['Laporan akhir', match (true) {
                    (bool) $score => 'Dinilai dosen: '.$score->total.'/100',
                    (bool) $final?->file => 'Terunggah, menunggu nilai dosen',
                    $allApproved => 'Siap diunggah',
                    default => 'Terbuka setelah semua bab disetujui',
                }, (bool) $final?->file],
            ];
            $now = collect($steps)->search(fn ($s) => ! $s[2]);
        ?>
        <ol class="rd-steps" aria-label="Langkah laporan KP">
            @foreach ($steps as $n => [$title, $sub, $done])
                <li @class(['rd-step', 'is-done' => $done, 'is-now' => $n === $now])>
                    <span class="rd-step-mark" aria-hidden="true">@if ($done)<x-ikon name="tick" :size="16" />@else{{ $n + 1 }}@endif</span>
                    <span class="rd-step-text min0">
                        <b>{{ $title }}</b>
                        <small>{{ $sub }}</small>
                    </span>
                    @if ($n === 0)
                        <a href="{{ route('laporan.draf') }}" class="btn btn-sm rd-step-go">Buka draf</a>
                    @elseif ($n === 2 && $allApproved && ! $final?->file && $canUpload)
                        <a href="#akhir" class="btn btn-sm btn-primary rd-step-go">Unggah</a>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    <div class="layout rd">
        <div class="col">
            @foreach ($chapters as $chapter)
                <?php
                    $thread = $threads->get($chapter->id, collect());
                    $canChat = $isStudent || $chapter->canBeReadBy($me);
                    $open = $thread->isNotEmpty() || $chapter->status === 'revisi' || ($isStudent && $current?->id === $chapter->id);
                ?>
                <details class="card rd-sec" id="bab-{{ $chapter->position }}" @if ($open) open @endif>
                    <summary class="rd-head">
                        <span @class(['rd-num', 'is-done' => $chapter->status === 'disetujui'])>@if ($chapter->status === 'disetujui')<x-ikon name="tick" :size="16" /><span class="sr-only">Bab {{ $chapter->position }} disetujui</span>@else{{ $chapter->position }}@endif</span>
                        <span class="rd-title min0">
                            <b>{{ $chapter->title }}</b>
                            <small>
                                <span class="rtl-status tone-{{ $chapter->statusTone() }}">{{ $chapter->statusLabel() }}</span>
                                @if ($chapter->needs_supervisor)<span>Ditinjau perusahaan dulu</span>@endif
                                @if ($thread->isNotEmpty())<span>{{ $thread->count() }} komentar</span>@endif
                            </small>
                        </span>
                    </summary>

                    <div class="rd-body">
                        @if ($chapter->status === 'revisi' && $chapter->revision_note)
                            <p class="rtl-note"><b>{{ $chapter->reviser?->role === 'supervisor' ? 'Company Supervisor' : 'Dosen pembimbing' }} meminta revisi</b> {{ $chapter->revision_note }}</p>
                        @endif

                        @if ($chapter->file && $canChat)
                            <div class="rd-file-row">
                                <a href="{{ route('laporan.berkas', $chapter) }}" target="_blank" rel="noopener" class="rd-file">
                                    <x-ikon name="file" :size="18" />
                                    <span class="min0"><b>{{ $chapter->file->original_name }}</b><small>Berkas mahasiswa · versi {{ $chapter->version }}</small></span>
                                    <x-ikon name="download" :size="16" />
                                </a>
                                @if ($canUpload && $chapter->status !== 'disetujui')
                                    @include('laporan._upload-chapter', ['label' => $chapter->status === 'revisi' ? 'Unggah revisi' : 'Ganti berkas', 'primary' => $chapter->status === 'revisi'])
                                @endif
                            </div>
                            <?php $older = $chapter->versions->where('version', '<', $chapter->version); ?>
                            @if ($older->isNotEmpty())
                                <div class="rd-versions">
                                    <span>Versi sebelumnya</span>
                                    @foreach ($older as $v)
                                        <a href="{{ route('laporan.versi', $v) }}" target="_blank" rel="noopener" title="{{ $v->file?->original_name }}">v{{ $v->version }} <small>{{ $v->submitted_at?->locale('id')->translatedFormat('j M') }}</small></a>
                                    @endforeach
                                </div>
                            @endif
                        @elseif (! $chapter->file)
                            @if ($canUpload)
                                <div class="rd-drop">
                                    <span class="min0"><b>Belum diunggah</b><small>PDF atau Word, maks. 10 MB</small></span>
                                    @include('laporan._upload-chapter', ['label' => 'Unggah bab '.$chapter->position, 'primary' => true])
                                </div>
                            @else
                                <p class="rd-empty">Bab ini belum diunggah.</p>
                            @endif
                        @else
                            <p class="rd-empty">Bab ini menunggu persetujuan Company Supervisor sebelum bisa dibuka dosen.</p>
                        @endif

                        @include('laporan._thread', ['thread' => $thread])

                        @if ($canChat && ! $locked && ($chapter->file || $thread->isNotEmpty()))
                            @include('laporan._compose', ['chapterId' => $chapter->id, 'label' => $chapter->label()])
                        @endif
                    </div>
                </details>
            @endforeach

            {{-- Laporan akhir utuh: dibuka setelah semua bab disetujui dosen --}}
            <?php $thread = $threads->get('akhir', collect()); ?>
            <section @class(['card', 'rd-sec', 'rd-final', 'is-locked' => ! $allApproved && ! $final?->file]) id="akhir">
                <div class="rd-head">
                    <span class="rd-num" aria-hidden="true"><x-ikon name="{{ ! $allApproved && ! $final?->file ? 'lock' : 'syllabus' }}" :size="16" /></span>
                    <span class="rd-title min0">
                        <b>Laporan akhir</b>
                        <small>{{ $final?->file ? 'Versi '.$final->version.' · '.$final->submitted_at->locale('id')->translatedFormat('j M Y, H.i') : 'Gabungan semua bab dalam satu berkas' }}</small>
                    </span>
                    @if ($canUpload && $allApproved)
                        <form method="POST" action="{{ route('laporan.akhir', $internship) }}" enctype="multipart/form-data">
                            @csrf
                            <label @class(['btn', 'btn-sm', 'btn-primary' => ! $final?->file])>
                                <x-ikon name="upload" :size="16" /> {{ $final?->file ? 'Ganti berkas' : 'Unggah laporan akhir' }}
                                <input type="file" name="berkas" accept=".pdf,.docx" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                            </label>
                        </form>
                    @endif
                </div>
                <div class="rd-body">
                    @if ($final?->file)
                        <a href="{{ route('laporan.akhir.berkas', $internship) }}" target="_blank" rel="noopener" class="rd-file">
                            <x-ikon name="file" :size="18" />
                            <span class="min0"><b>{{ $final->file->original_name }}</b><small>Laporan akhir mahasiswa</small></span>
                            <x-ikon name="download" :size="16" />
                        </a>
                    @elseif (! $allApproved)
                        <p class="rd-empty">Terbuka setelah semua bab disetujui dosen pembimbing ({{ $approved }} dari {{ $chapters->count() }} sekarang).</p>
                    @else
                        <p class="rd-empty">{{ $isStudent ? 'Semua bab sudah disetujui. Gabungkan menjadi satu berkas lalu unggah. PDF atau Word, maks. 10 MB.' : 'Mahasiswa belum mengunggah laporan akhir.' }}</p>
                    @endif

                    @include('laporan._thread', ['thread' => $thread])
                    @if (! $locked && ($final?->file || $thread->isNotEmpty()))
                        @include('laporan._compose', ['chapterId' => null, 'label' => 'laporan akhir'])
                    @endif
                </div>
            </section>
        </div>

        {{-- Nilai laporan akhir --}}
        <div class="col">
            <section class="card rd-grade" id="nilai">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Nilai laporan akhir</h2>
                        <p class="card-sub">{{ $locked ? 'Sudah disahkan kaprodi' : 'Dinilai dosen pembimbing, disahkan kaprodi' }}</p>
                    </div>
                </div>
                @if ($isLecturer && ! $locked)
                    <form method="POST" action="{{ route('laporan.nilai', $internship) }}" class="card-body rd-grade-form" data-rd-grade>
                        @csrf
                        @method('PUT')
                        <div class="rd-aspects">
                            @foreach (\App\Models\ReportScore::ASPECTS as $key => [$label, $max])
                                <div class="rd-aspect">
                                    <label for="nilai-{{ $key }}">{{ $label }}</label>
                                    <span class="rd-pts">
                                        <input id="nilai-{{ $key }}" type="number" name="{{ $key }}" min="0" max="{{ $max }}" step="1" inputmode="numeric" placeholder="0" required value="{{ old($key, $score?->{$key}) }}" @disabled(! $final?->file)>
                                        <small aria-hidden="true">/{{ $max }}</small>
                                    </span>
                                    @error($key)<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                        <div class="rd-total">
                            <span>Total</span>
                            <span class="rd-total-val"><b data-rd-total>{{ $score?->total ?? 0 }}</b><small>/100</small></span>
                        </div>
                        <div class="field">
                            <label class="label" for="nilai-note">Catatan <span class="label-opt">(opsional)</span></label>
                            <textarea id="nilai-note" name="note" class="textarea" rows="2" maxlength="1000" @disabled(! $final?->file)>{{ old('note', $score?->note) }}</textarea>
                        </div>
                        @if ($final?->file)
                            <button type="submit" class="btn btn-primary">{{ $score ? 'Perbarui nilai' : 'Simpan nilai' }}</button>
                        @else
                            <p class="hint">Nilai bisa diisi setelah mahasiswa mengunggah laporan akhir.</p>
                        @endif
                    </form>
                @elseif ($score)
                    <div class="card-body">
                        <dl class="rd-scores">
                            @foreach (\App\Models\ReportScore::ASPECTS as $key => [$label, $max])
                                <div><dt>{{ $label }}</dt><dd><b>{{ $score->{$key} }}</b> / {{ $max }}</dd></div>
                            @endforeach
                        </dl>
                        <div class="rd-total"><span>Total</span><span class="rd-total-val"><b>{{ $score->total }}</b><small>/100</small></span></div>
                        @if ($score->note)<p class="rd-note">{{ $score->note }}</p>@endif
                    </div>
                @else
                    <div class="card-body"><p class="rd-empty">Belum dinilai.</p></div>
                @endif
            </section>
        </div>
    </div>

    <script>
        (function () {
            // Buka bab yang dituju tautan notifikasi (#bab-2)
            var open = function () {
                var el = location.hash && document.querySelector(location.hash);
                if (el && el.tagName === 'DETAILS') el.open = true;
            };
            open();
            window.addEventListener('hashchange', open);

            // Total nilai mengikuti isian
            var form = document.querySelector('[data-rd-grade]');
            if (!form) return;
            var total = form.querySelector('[data-rd-total]');
            var inputs = [].slice.call(form.querySelectorAll('input[type=number]'));
            var sum = function () {
                total.textContent = inputs.reduce(function (t, i) {
                    var v = parseInt(i.value, 10), max = parseInt(i.max, 10);
                    return t + (isNaN(v) ? 0 : Math.max(0, Math.min(v, max)));
                }, 0);
            };
            inputs.forEach(function (i) { i.addEventListener('input', sum); });
        })();
    </script>
@endsection
