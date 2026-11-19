{{--
    Ulasan tempat magang (halaman Status KP, setelah masa magang berakhir).
    Tiga aspek dengan bintang 1–5, rekomendasi ya/tidak, komentar. Bisa diperbarui.
    Variabel: internship, review (CompanyReview|null)
--}}
<?php $bag = $errors->getBag('ulasan'); ?>
<section class="card rv-card" id="ulasan">
    <div class="card-head">
        <div>
            <h2 class="card-title">{{ $review ? 'Ulasanmu tentang '.$internship->company?->name : 'Bagaimana pengalaman magangmu?' }}</h2>
            <p class="card-sub">{{ $review ? 'Terima kasih. Kamu masih bisa memperbaruinya.' : 'Ulasanmu tampil tanpa nama di halaman Lowongan dan membantu adik tingkat memilih tempat magang.' }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('ulasan.simpan') }}" class="card-body rv-form">
        @csrf
        @foreach (\App\Models\CompanyReview::ASPECTS as $key => [$label, $hint])
            <fieldset class="rv-aspect">
                <legend><b>{{ $label }}</b><small>{{ $hint }}</small></legend>
                <span class="rv-rate">
                    @for ($n = 5; $n >= 1; $n--)
                        <input type="radio" id="rv-{{ $key }}-{{ $n }}" name="{{ $key }}" value="{{ $n }}" @checked((int) old($key, $review?->{$key}) === $n) required>
                        <label for="rv-{{ $key }}-{{ $n }}" title="{{ $n }} dari 5"><span class="sr-only">{{ $n }} dari 5</span></label>
                    @endfor
                </span>
                @if ($bag->has($key))<p class="field-error">{{ $bag->first($key) }}</p>@endif
            </fieldset>
        @endforeach

        <fieldset class="rv-aspect">
            <legend><b>Rekomendasikan ke adik tingkat?</b></legend>
            <span class="rv-choice">
                <?php $rec = old('recommend', $review ? (int) $review->recommend : null); ?>
                <label><input type="radio" name="recommend" value="1" @checked($rec !== null && (int) $rec === 1) required> Ya</label>
                <label><input type="radio" name="recommend" value="0" @checked($rec !== null && (int) $rec === 0)> Tidak</label>
            </span>
        </fieldset>

        <div class="field">
            <label class="label" for="rv-comment">Cerita singkat <span class="label-opt">(opsional)</span></label>
            <textarea id="rv-comment" name="comment" class="textarea" rows="3" maxlength="1000" placeholder="Apa yang paling kamu pelajari? Apa yang perlu disiapkan adik tingkat?">{{ old('comment', $review?->comment) }}</textarea>
        </div>
        <div class="row-actions">
            <button type="submit" class="btn btn-primary">{{ $review ? 'Perbarui ulasan' : 'Kirim ulasan' }}</button>
        </div>
    </form>
</section>
