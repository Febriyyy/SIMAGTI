@extends('layouts.app')

@section('title', 'Lowongan Magang')

@section('content')
    <?php
        $isMatch = fn ($p) => $p->skills->contains(fn ($s) => in_array($s->id, $mySkills, true));
        $isSoon = fn ($p) => $p->deadline && today()->diffInDays($p->deadline, false) >= 0 && today()->diffInDays($p->deadline, false) <= 7;
        $matchCount = $postings->filter($isMatch)->count();
        $soonCount = $postings->filter($isSoon)->count();
        $companies = $postings->pluck('company')->unique('id')->values();
        $nearest = $postings->filter(fn ($p) => $p->deadline && today()->diffInDays($p->deadline, false) >= 0)->sortBy('deadline')->first();
        $initials = fn (string $name) => mb_strtoupper(collect(preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|Yayasan)\.?\s+/i', '', $name))))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
    ?>
    <section class="jb-head">
        <div class="jb-head-text">
            <h1 class="jb-head-title">Saatnya mulai magang.</h1>
            <p class="jb-head-sub">Pilih lowongan dari perusahaan mitra kampus. Data perusahaan langsung terisi saat kamu mengajukan KP.</p>
            @unless ($hasPortfolio)
                <a href="{{ route('portofolio.edit') }}" class="jb-head-link">Lengkapi portofolio agar skillmu terlihat perusahaan <x-ikon name="arrow" :size="16" /></a>
            @endunless
        </div>
        @if ($postings->isNotEmpty())
            <div class="jb-head-side">
                <div class="jb-head-orgs" aria-hidden="true">
                    @foreach ($companies->take(4) as $n => $c)<span class="jb-head-org jb-head-org-{{ $n % 4 }}">{{ $initials($c->name) }}</span>@endforeach
                    @if ($companies->count() > 4)<span class="jb-head-org jb-head-org-more">+{{ $companies->count() - 4 }}</span>@endif
                </div>
                <p class="jb-head-count"><b>{{ $postings->count() }} lowongan</b> dari {{ $companies->count() }} perusahaan mitra</p>
                @if ($nearest)<p class="jb-head-near">Tutup terdekat {{ $nearest->deadline->locale('id')->translatedFormat('j F Y') }}</p>@endif
            </div>
        @endif
        @if ($postings->isNotEmpty())
            <div class="jb-tools">
                <label class="jb-search">
                    <x-ikon name="search" :size="18" />
                    <span class="sr-only">Cari lowongan</span>
                    <input type="search" placeholder="Cari posisi, perusahaan, atau skill" autocomplete="off" data-jb-search>
                </label>
                <div class="jb-filters" role="group" aria-label="Saring lowongan">
                    <button type="button" class="jb-filter is-on" data-jb-filter="all" aria-pressed="true">Semua <span>{{ $postings->count() }}</span></button>
                    <button type="button" class="jb-filter" data-jb-filter="match" aria-pressed="false" @disabled($matchCount === 0)>Cocok dengan skillku <span>{{ $matchCount }}</span></button>
                    <button type="button" class="jb-filter" data-jb-filter="soon" aria-pressed="false" @disabled($soonCount === 0)>Segera tutup <span>{{ $soonCount }}</span></button>
                </div>
            </div>
        @endif
    </section>

    @if (! $canApply)
        <div class="alert alert-info" role="status">Anda sudah punya pengajuan atau KP yang berjalan. Lowongan tetap bisa dilihat sebagai referensi.</div>
    @endif

    @if ($postings->isEmpty())
        <section class="card"><div class="empty"><strong>Belum ada lowongan dibuka</strong> Sambil menunggu, lengkapi portofolio agar siap saat lowongan dibuka.</div></section>
    @else
        <ul class="jb-list">
            @foreach ($postings as $p)
                <?php
                    $left = $p->deadline ? (int) today()->diffInDays($p->deadline, false) : null;
                    $matched = $p->skills->filter(fn ($s) => in_array($s->id, $mySkills, true));
                ?>
                <li class="jb-row" data-jb-card data-match="{{ $isMatch($p) ? 1 : 0 }}" data-soon="{{ $isSoon($p) ? 1 : 0 }}" data-text="{{ mb_strtolower($p->title.' '.$p->company->name.' '.($p->location ?? '').' '.$p->skills->pluck('name')->implode(' ')) }}">
                    <x-org :name="$p->company->name" class="jb-row-org" />

                    <div class="jb-row-main">
                        <h2 class="jb-row-title"><button type="button" data-open="job-{{ $p->id }}">{{ $p->title }}</button></h2>
                        <p class="jb-row-co">{{ $p->company->name }}</p>
                        <p class="jb-row-meta">
                            <span>{{ $p->location ?: ($p->company->city ?: 'Lokasi menyusul') }}</span>
                            <span>{{ $p->quota ? $p->quota.' orang' : 'Kuota terbuka' }}</span>
                            @if ($p->company->industry)<span>{{ $p->company->industry }}</span>@endif
                        </p>
                        @if ($p->skills->isNotEmpty())
                            <ul class="jb-row-skills" aria-label="Keahlian">
                                @foreach ($p->skills->take(5) as $skill)
                                    <li @class(['is-match' => in_array($skill->id, $mySkills, true)])>{{ $skill->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="jb-row-side">
                        @if ($left !== null)
                            <span @class(['jb-row-due', 'is-soon' => $left <= 7])>{{ $left === 0 ? 'Tutup hari ini' : ($left <= 7 ? 'Tutup '.$left.' hari lagi' : 'Tutup '.$p->deadline->locale('id')->translatedFormat('j M Y')) }}</span>
                        @endif
                        @if ($p->skills->isNotEmpty() && $matched->isNotEmpty())
                            <span class="jb-row-fit" title="Skill di portofoliomu yang dicari lowongan ini"><b>{{ $matched->count() }}/{{ $p->skills->count() }}</b> skill cocok</span>
                        @endif
                        <div class="jb-row-actions">
                            <button type="button" class="btn btn-sm" data-open="job-{{ $p->id }}">Detail</button>
                            @if ($canApply)
                                <a href="{{ route('kp.status', ['lowongan' => $p->id]) }}#ajukan" class="btn btn-sm btn-primary">Ajukan KP</a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="jb-none" data-jb-none hidden>
            <b>Tidak ada lowongan yang cocok</b>
            <p>Coba kata kunci lain atau tampilkan semua lowongan.</p>
            <button type="button" class="btn btn-sm" data-jb-reset>Tampilkan semua</button>
        </div>
        <script>
            (function () {
                var cards = [].slice.call(document.querySelectorAll('[data-jb-card]'));
                var input = document.querySelector('[data-jb-search]');
                var buttons = [].slice.call(document.querySelectorAll('[data-jb-filter]'));
                var none = document.querySelector('[data-jb-none]');
                var mode = 'all';
                var apply = function () {
                    var q = (input.value || '').trim().toLowerCase();
                    var shown = 0;
                    cards.forEach(function (c) {
                        var ok = (mode === 'all' || c.getAttribute('data-' + mode) === '1') && (!q || c.getAttribute('data-text').indexOf(q) !== -1);
                        c.hidden = !ok;
                        if (ok) shown++;
                    });
                    none.hidden = shown > 0;
                };
                buttons.forEach(function (b) {
                    b.addEventListener('click', function () {
                        mode = b.getAttribute('data-jb-filter');
                        buttons.forEach(function (x) { var on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-pressed', on ? 'true' : 'false'); });
                        apply();
                    });
                });
                if (input) input.addEventListener('input', apply);
                document.querySelector('[data-jb-reset]').addEventListener('click', function () { input.value = ''; buttons[0].click(); });
            })();
        </script>

        @foreach ($postings as $p)
            <dialog id="job-{{ $p->id }}" class="dialog">
                <div class="dialog-body">
                    <div class="dialog-head">
                        <div class="job-dlg-head">
                            <x-org :name="$p->company->name" class="job-card-org" />
                            <div class="min0">
                                <h2 class="card-title">{{ $p->title }}</h2>
                                <p class="card-sub">{{ $p->company->name }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                    </div>
                    <div class="job-dlg-section">
                        <h3>Deskripsi pekerjaan</h3>
                        <p>{!! nl2br(e($p->description)) !!}</p>
                    </div>
                    @if ($p->requirements)
                        <div class="job-dlg-section">
                            <h3>Syarat</h3>
                            <p>{!! nl2br(e($p->requirements)) !!}</p>
                        </div>
                    @endif
                    <dl class="job-card-facts">
                        <div><dt>Lokasi</dt><dd>{{ $p->location ?: ($p->company->city ?: 'Menyusul') }}</dd></div>
                        <div><dt>Kuota</dt><dd>{{ $p->quota ? $p->quota.' orang' : 'Terbuka' }}</dd></div>
                        <div><dt>Tutup</dt><dd>{{ $p->deadline?->locale('id')->translatedFormat('j F Y') ?? 'Belum ditentukan' }}</dd></div>
                    </dl>
                    @if ($p->company->address)<p class="muted job-dlg-addr">{{ $p->company->address }}</p>@endif
                    <div class="dialog-foot row-actions">
                        <button type="button" class="btn" data-close>Tutup</button>
                        @if ($canApply)
                            <a href="{{ route('kp.status', ['lowongan' => $p->id]) }}#ajukan" class="btn btn-primary">Ajukan KP di sini <x-ikon name="arrow" :size="16" /></a>
                        @endif
                    </div>
                </div>
            </dialog>
        @endforeach
    @endif
@endsection
