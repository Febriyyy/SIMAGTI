@extends('layouts.app')

@section('title', 'Laporan KP')

{{--
    Laporan KP bersama mahasiswa dan dosen pembimbing. Tiap bab punya utas diskusi (boleh berlampiran
    berkas koreksi); laporan akhir utuh diunggah mahasiswa lalu dinilai dosen pada empat aspek.
--}}
@section('content')
    <?php
        $me = auth()->user();
        $final = $internship->finalReport;
        $score = $internship->reportScore;
        $student = $internship->student;
    ?>
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ $isStudent ? 'Laporan KP' : 'Laporan '.$student->name }}</h1>
            <p class="page-sub">
                @if ($isStudent)
                    Diskusi tiap bab dan laporan akhir dengan {{ $internship->lecturer?->listName() ?? 'dosen pembimbing' }}.
                @else
                    {{ $student->name }} · {{ $internship->placeName() }}
                @endif
            </p>
        </div>
        <a href="{{ $isStudent ? route('dokumen.index').'#laporan' : route('laporan.antrean') }}" class="btn btn-ghost"><x-ikon name="back" :size="16" /> Kembali</a>
    </div>

    <div class="layout rd">
        <div class="col">
            @foreach ($chapters as $chapter)
                <?php
                    $thread = $threads->get($chapter->id, collect());
                    $canChat = $isStudent || $chapter->canBeReadBy($me);
                ?>
                <details class="card rd-sec" id="bab-{{ $chapter->position }}" @if ($thread->isNotEmpty() || $chapter->status === 'revisi') open @endif>
                    <summary class="rd-head">
                        <span class="rd-num" aria-hidden="true">{{ $chapter->position }}</span>
                        <span class="rd-title min0">
                            <b>{{ $chapter->title }}</b>
                            <small>
                                <span class="rtl-status tone-{{ $chapter->statusTone() }}">{{ $chapter->statusLabel() }}</span>
                                @if ($thread->isNotEmpty())<span>{{ $thread->count() }} komentar</span>@endif
                            </small>
                        </span>
                    </summary>

                    <div class="rd-body">
                        @if ($chapter->file && $canChat)
                            <a href="{{ route('laporan.berkas', $chapter) }}" target="_blank" rel="noopener" class="rd-file">
                                <x-ikon name="file" :size="18" />
                                <span class="min0"><b>{{ $chapter->file->original_name }}</b><small>Berkas mahasiswa · versi {{ $chapter->version }}</small></span>
                                <x-ikon name="download" :size="16" />
                            </a>
                        @elseif (! $chapter->file)
                            <p class="rd-empty">Bab ini belum diunggah.</p>
                        @else
                            <p class="rd-empty">Bab ini menunggu persetujuan Company Supervisor sebelum bisa dibuka dosen.</p>
                        @endif

                        @include('laporan._thread', ['thread' => $thread])

                        @if ($canChat)
                            @include('laporan._compose', ['chapterId' => $chapter->id, 'label' => $chapter->label()])
                        @endif
                    </div>
                </details>
            @endforeach

            {{-- Laporan akhir utuh --}}
            <?php $thread = $threads->get('akhir', collect()); ?>
            <section class="card rd-sec rd-final" id="akhir">
                <div class="rd-head">
                    <span class="rd-num" aria-hidden="true"><x-ikon name="syllabus" :size="16" /></span>
                    <span class="rd-title min0">
                        <b>Laporan akhir</b>
                        <small>{{ $final?->file ? 'Versi '.$final->version.' · '.$final->submitted_at->locale('id')->translatedFormat('j M Y, H.i') : 'Gabungan semua bab dalam satu berkas' }}</small>
                    </span>
                    @if ($isStudent && ! $locked)
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
                    @else
                        <p class="rd-empty">{{ $isStudent ? 'Unggah laporan akhir setelah semua bab rampung. PDF atau Word, maks. 10 MB.' : 'Mahasiswa belum mengunggah laporan akhir.' }}</p>
                    @endif

                    @include('laporan._thread', ['thread' => $thread])
                    @include('laporan._compose', ['chapterId' => null, 'label' => 'laporan akhir'])
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
