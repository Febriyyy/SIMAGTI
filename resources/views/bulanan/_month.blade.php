{{--
    Satu bulan KP: ringkasan data, refleksi mahasiswa, nilai singkat peninjau, catatan dosen pembimbing.
    $entry = ['month', 'review', 'summary']; $canEdit (mahasiswa), $canReview (peninjau), $canNote (dosen pembimbing magang)
--}}
@php
    $r = $entry['review'];
    $s = $entry['summary'];
    $m = $entry['month'];
    $canEdit = $canEdit ?? false;
    $canReview = ($canReview ?? false) && $r->isOpenForReview() && ! $r->lecturer_noted_at;
    $canNote = ($canNote ?? false) && ($r->isReviewed() || $r->hasEnded());
    $reviewerLabel = $internship->isSeminar() ? 'Dosen pembimbing' : 'Company Supervisor';
    $goals = $internship->syllabusItems->whereIn('id', $r->syllabus_item_ids ?? []);
@endphp
<section class="card month" id="bulan-{{ $m }}">
    <div class="card-head">
        <div>
            <h2 class="card-title">{{ $r->label() }}</h2>
            <p class="card-sub">{{ $r->rangeLabel() }}</p>
        </div>
        <x-chip :tone="$r->statusTone()">{{ $r->statusLabel() }}</x-chip>
    </div>

    <div class="card-body">
        <dl class="month-facts">
            <div><dt>Jam tercatat</dt><dd>{{ \App\Support\Num::short($s['hours']) }} jam</dd></div>
            <div><dt>Hari aktif</dt><dd>{{ $s['days'] }} hari</dd></div>
            <div><dt>Task selesai</dt><dd>{{ $s['done'] }}</dd></div>
            @foreach (\App\Models\SyllabusItem::CATEGORIES as $key => $cat)
                <div><dt>{{ is_array($cat) ? $cat[0] : $cat }}</dt><dd>{{ $s['by_category'][$key] ?? 0 }} task</dd></div>
            @endforeach
        </dl>

        {{-- Refleksi mahasiswa --}}
        <h3 class="month-h">Refleksi mahasiswa</h3>
        @if ($canEdit && $r->isOpenForReflection() && ! $r->isReviewed())
            <form method="POST" action="{{ route('bulanan.update', $m) }}" class="stack">
                @csrf
                @method('PATCH')
                <div class="field">
                    <label class="label" for="learned-{{ $m }}">Yang dipelajari</label>
                    <textarea id="learned-{{ $m }}" name="learned" class="textarea" rows="3" maxlength="2000" required>{{ old('learned', $r->learned) }}</textarea>
                </div>
                <div class="field">
                    <label class="label" for="obstacles-{{ $m }}">Kendala</label>
                    <textarea id="obstacles-{{ $m }}" name="obstacles" class="textarea" rows="2" maxlength="2000">{{ old('obstacles', $r->obstacles) }}</textarea>
                </div>
                <div class="field">
                    <label class="label" for="plan-{{ $m }}">Rencana bulan depan</label>
                    <textarea id="plan-{{ $m }}" name="plan" class="textarea" rows="2" maxlength="2000">{{ old('plan', $r->plan) }}</textarea>
                </div>
                @if ($internship->syllabusItems->isNotEmpty())
                    <fieldset class="goal-pick">
                        <legend class="label">Tujuan silabus yang ada kemajuannya</legend>
                        @foreach ($internship->syllabusItems as $item)
                            <label class="check-row">
                                <input type="checkbox" name="syllabus_item_ids[]" value="{{ $item->id }}" @checked(in_array($item->id, old('syllabus_item_ids', $r->syllabus_item_ids ?? [])))>
                                <span>{{ $item->title }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm"><x-ikon name="send" :size="16" /> {{ $r->isReflected() ? 'Perbarui refleksi' : 'Kirim refleksi' }}</button></div>
            </form>
        @elseif ($r->isReflected())
            <dl class="answers">
                <div><dt>Yang dipelajari</dt><dd>{{ $r->learned }}</dd></div>
                @if ($r->obstacles)<div><dt>Kendala</dt><dd>{{ $r->obstacles }}</dd></div>@endif
                @if ($r->plan)<div><dt>Rencana</dt><dd>{{ $r->plan }}</dd></div>@endif
                @if ($goals->isNotEmpty())<div><dt>Tujuan silabus</dt><dd>{{ $goals->pluck('title')->join(', ') }}</dd></div>@endif
            </dl>
        @elseif ($canEdit)
            <p class="muted">Refleksi bisa diisi mulai {{ $r->period_end->copy()->subDays(7)->locale('id')->translatedFormat('j F') }}.</p>
        @else
            <p class="muted">{{ $r->hasEnded() ? 'Mahasiswa belum mengisi refleksi.' : 'Bulan ini masih berjalan.' }}</p>
        @endif

        {{-- Nilai singkat peninjau --}}
        <h3 class="month-h">Nilai dari {{ $reviewerLabel }}</h3>
        @if ($canReview)
            <form method="POST" action="{{ route('bulanan.review', [$internship, $m]) }}" class="stack">
                @csrf
                @method('PATCH')
                <div class="score-grid">
                    @foreach (\App\Models\MonthlyReview::CATEGORIES as $key => [$col, $label])
                        <fieldset class="score-pick">
                            <legend>{{ $label }}</legend>
                            <div class="segmented">
                                @foreach (\App\Models\MonthlyReview::SCALE as $v => $text)
                                    <label class="seg-radio" title="{{ $text }}">
                                        <input type="radio" name="{{ $col }}" value="{{ $v }}" @checked((int) old($col, $r->{$col}) === $v) required>
                                        <span>{{ $v }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                <p class="hint">1 = perlu bimbingan · 2 = cukup · 3 = baik · 4 = sangat baik. Cukup satu menit, tidak menghambat kegiatan mahasiswa.</p>
                <div class="field">
                    <label class="label" for="rnote-{{ $m }}">Catatan singkat (opsional)</label>
                    <textarea id="rnote-{{ $m }}" name="reviewer_note" class="textarea" rows="2" maxlength="1000">{{ old('reviewer_note', $r->reviewer_note) }}</textarea>
                </div>
                <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm"><x-ikon name="check" :size="16" /> Simpan nilai</button></div>
            </form>
        @elseif ($r->isReviewed())
            <div class="score-show">
                @foreach (\App\Models\MonthlyReview::CATEGORIES as $key => [$col, $label])
                    <div class="score-pill"><span>{{ $label }}</span><strong>{{ $r->{$col} }}</strong><small>{{ \App\Models\MonthlyReview::SCALE[$r->{$col}] ?? '' }}</small></div>
                @endforeach
            </div>
            @if ($r->reviewer_note)<p class="quote">“{{ $r->reviewer_note }}” <small>— {{ $r->reviewer?->listName() }}</small></p>@endif
        @else
            <p class="muted">{{ $r->isOpenForReview() ? 'Belum dinilai.' : 'Bisa dinilai setelah refleksi masuk atau bulan berakhir.' }}</p>
        @endif

        {{-- Catatan dosen pembimbing (KP Industri / MBKM) --}}
        @if (! $internship->isSeminar())
            <h3 class="month-h">Catatan dosen pembimbing</h3>
            @if ($canNote)
                <form method="POST" action="{{ route('bulanan.catatan', [$internship, $m]) }}" class="stack">
                    @csrf
                    @method('PATCH')
                    <textarea name="lecturer_note" class="textarea" rows="2" maxlength="1000" required aria-label="Catatan dosen pembimbing bulan ke-{{ $m }}">{{ old('lecturer_note', $r->lecturer_note) }}</textarea>
                    <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm">Simpan catatan</button></div>
                </form>
            @elseif ($r->lecturer_note)
                <p class="quote">“{{ $r->lecturer_note }}” <small>— {{ $r->lecturer?->listName() }}</small></p>
            @else
                <p class="muted">Belum ada catatan.</p>
            @endif
        @endif
    </div>
</section>
