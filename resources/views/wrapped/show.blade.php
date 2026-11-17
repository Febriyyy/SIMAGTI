<?php
    // KP Wrapped: cerita layar penuh ala Spotify Wrapped. Slide berganti otomatis (berhenti bila pengguna memilih
    // kurangi gerak), bisa diketuk/digeser dengan tombol atau panah keyboard. Slide terakhir: simpan kartu ringkasan (PNG).
    $num = fn ($n) => \App\Support\Num::short((float) $n);
    $slides = ['intro', 'days', 'tasks'];
    if ($w['bestWeek']) { $slides[] = 'week'; }
    if ($w['favoriteDay']) { $slides[] = 'day'; }
    if ($w['goalsTotal'] > 0) { $slides[] = 'goals'; }
    $slides[] = 'guidance';
    if ($w['grade']) { $slides[] = 'grade'; }
    $slides[] = 'outro';
    // Data kartu ringkasan untuk gambar PNG (digambar di canvas)
    $share = [
        'year' => $w['year'], 'name' => $w['name'],
        'place' => ($w['position'] ? $w['position'].' · ' : '').$w['place'],
        'days' => $w['days'], 'done' => $w['done'], 'tasks' => $w['tasks'],
        'goals' => count($w['goals']).'/'.$w['goalsTotal'],
        'last' => $w['grade'] ?: (string) $w['guidance'],
        'lastLabel' => $w['grade'] ? 'Nilai akhir' : 'Bimbingan',
    ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>KP Wrapped {{ $w['year'] }} · {{ $w['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="wr-body">
<main class="wr" data-wrapped aria-roledescription="cerita" aria-label="KP Wrapped {{ $w['year'] }}">
    <div class="wr-progress" aria-hidden="true">
        @foreach ($slides as $s)<span><i></i></span>@endforeach
    </div>
    <div class="wr-top">
        <img src="{{ asset('images/logo-simagti-mark.svg') }}" alt="" width="28" height="28">
        <span>KP Wrapped {{ $w['year'] }}</span>
        <button type="button" class="wr-icon-btn" data-wr-pause aria-label="Jeda"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg></button>
        <a href="{{ route('dashboard') }}" class="wr-icon-btn" aria-label="Tutup"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></a>
    </div>

    <div class="wr-stage" aria-live="polite">
        @foreach ($slides as $i => $s)
            <section @class(['wr-slide', 'wr-'.$s, 'is-active' => $i === 0]) data-slide="{{ $i }}" aria-label="Slide {{ $i + 1 }} dari {{ count($slides) }}" @if ($i !== 0) hidden @endif>
                @switch($s)
                    @case('intro')
                        <p class="wr-kicker">KP Wrapped {{ $w['year'] }}</p>
                        <h1 class="wr-huge">Hai, {{ $w['firstName'] }}!</h1>
                        <p class="wr-lead">KP kamu di <b>{{ $w['place'] }}</b> sudah selesai. Yuk lihat perjalananmu.</p>
                        @break
                    @case('days')
                        <p class="wr-kicker">Konsistensi</p>
                        <p class="wr-number" data-count="{{ $w['days'] }}">{{ $w['days'] }}</p>
                        <p class="wr-label">hari kamu mencatat kegiatan</p>
                        <p class="wr-lead">selama {{ $w['weeks'] }} minggu, total {{ $w['entries'] }} catatan.</p>
                        @break
                    @case('tasks')
                        <p class="wr-kicker">Hasil kerja</p>
                        <div class="wr-ring" style="--p: {{ $w['percent'] }}"><span data-count="{{ $w['done'] }}">{{ $w['done'] }}</span></div>
                        <p class="wr-label">task selesai</p>
                        <p class="wr-lead">dari {{ $w['tasks'] }} task. {{ $w['percent'] >= 80 ? 'Luar biasa!' : ($w['percent'] >= 50 ? 'Kerja bagus!' : 'Setiap langkah dihitung.') }}</p>
                        @break
                    @case('week')
                        <p class="wr-kicker">Minggu terbaikmu</p>
                        <p class="wr-number">Minggu ke-{{ $w['bestWeek']['number'] }}</p>
                        <p class="wr-label">{{ $w['bestWeek']['count'] }} kegiatan dalam seminggu</p>
                        <p class="wr-lead">{{ $w['bestWeek']['from']->locale('id')->translatedFormat('j M') }} – {{ $w['bestWeek']['to']->locale('id')->translatedFormat('j M Y') }}</p>
                        @break
                    @case('day')
                        <p class="wr-kicker">Hari paling rajin</p>
                        <p class="wr-number">{{ $w['favoriteDay']['name'] }}</p>
                        <p class="wr-label">{{ $w['favoriteDay']['count'] }} catatan di hari ini</p>
                        @if ($w['longest'] > 1)<p class="wr-lead">Rentetan terpanjangmu <b>{{ $w['longest'] }} hari</b> berturut-turut.</p>@endif
                        @break
                    @case('goals')
                        <p class="wr-kicker">Goals</p>
                        <p class="wr-number"><span data-count="{{ count($w['goals']) }}">{{ count($w['goals']) }}</span><small>/{{ $w['goalsTotal'] }}</small></p>
                        <p class="wr-label">goal tercapai</p>
                        @if ($w['goals'])
                            <ul class="wr-list">@foreach (array_slice($w['goals'], 0, 4) as $g)<li>{{ $g }}</li>@endforeach</ul>
                        @endif
                        @break
                    @case('guidance')
                        <p class="wr-kicker">Bimbingan</p>
                        <p class="wr-number" data-count="{{ $w['guidance'] }}">{{ $w['guidance'] }}</p>
                        <p class="wr-label">sesi bimbingan dikonfirmasi</p>
                        <p class="wr-lead">Terima kasih untuk dosen pembimbing dan Company Supervisor yang menemanimu.</p>
                        @break
                    @case('grade')
                        <p class="wr-kicker">Hasil akhir</p>
                        <div class="wr-grade-badge">{{ $w['grade'] }}</div>
                        @if ($w['score'] !== null)<p class="wr-label">nilai akhir {{ $num($w['score']) }}</p>@endif
                        @if ($w['result'])<p class="wr-lead">KP dinyatakan <b>{{ mb_strtolower($w['result']) }}</b>. Selamat!</p>@endif
                        @break
                    @case('outro')
                        <p class="wr-kicker">Rangkuman</p>
                        <div class="wr-card" data-wr-card>
                            <div class="wr-card-head"><b>KP Wrapped {{ $w['year'] }}</b><span>{{ $w['name'] }}</span></div>
                            <div class="wr-card-place">{{ $w['position'] ? $w['position'].' · ' : '' }}{{ $w['place'] }}</div>
                            <dl class="wr-card-grid">
                                <div><dt>Hari tercatat</dt><dd>{{ $w['days'] }}</dd></div>
                                <div><dt>Task selesai</dt><dd>{{ $w['done'] }}/{{ $w['tasks'] }}</dd></div>
                                <div><dt>Goal tercapai</dt><dd>{{ count($w['goals']) }}/{{ $w['goalsTotal'] }}</dd></div>
                                <div><dt>{{ $w['grade'] ? 'Nilai akhir' : 'Bimbingan' }}</dt><dd>{{ $w['grade'] ?: $w['guidance'] }}</dd></div>
                            </dl>
                        </div>
                        <div class="wr-actions">
                            <button type="button" class="wr-btn wr-btn-main" data-wr-save>Simpan gambar</button>
                            <button type="button" class="wr-btn" data-wr-restart>Putar ulang</button>
                        </div>
                        @break
                @endswitch
            </section>
        @endforeach
    </div>

    <button type="button" class="wr-nav wr-prev" data-wr-prev aria-label="Slide sebelumnya"></button>
    <button type="button" class="wr-nav wr-next" data-wr-next aria-label="Slide berikutnya"></button>

    <script type="application/json" data-wr-data>{!! json_encode($share, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
</main>

<script>
(function () {
    var root = document.querySelector('[data-wrapped]');
    var slides = [].slice.call(root.querySelectorAll('.wr-slide'));
    var bars = [].slice.call(root.querySelectorAll('.wr-progress i'));
    var pauseBtn = root.querySelector('[data-wr-pause]');
    var reduce = false;
    try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
    var PAUSE = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>', PLAY = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>';
    var DURATION = 6000, index = 0, timer = null, paused = reduce, started = 0, elapsed = 0;

    function countUp(slide) {
        slide.querySelectorAll('[data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10);
            if (reduce || !(target > 1)) { el.textContent = target; return; }
            var t0 = null;
            function step(ts) { if (t0 === null) t0 = ts; var p = Math.min(1, (ts - t0) / 900); el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); }
            el.textContent = '0'; requestAnimationFrame(step);
            setTimeout(function () { el.textContent = target; }, 1300);
        });
    }
    // Konfeti ringan saat slide nilai/rangkuman muncul (dilewati bila pengguna memilih kurangi gerak)
    function burst(slide) {
        if (reduce || !(slide.classList.contains('wr-grade') || slide.classList.contains('wr-outro'))) return;
        var old = slide.querySelector('.wr-confetti'); if (old) old.remove();
        var box = document.createElement('div'); box.className = 'wr-confetti'; box.setAttribute('aria-hidden', 'true');
        var colors = ['#a7f3d0', '#ffffff', '#fde68a', '#93c5fd', '#f9a8d4', '#6ee7b7'];
        for (var n = 0; n < 64; n++) {
            var p = document.createElement('i'), a = Math.random() * Math.PI * 2, dist = 90 + Math.random() * 220;
            if (n % 3 === 0) p.className = 'dot';
            p.style.setProperty('--c', colors[n % colors.length]);
            p.style.setProperty('--dx', Math.round(Math.cos(a) * dist * 1.3) + 'px');
            p.style.setProperty('--dy', Math.round(Math.sin(a) * dist - 80) + 'px');
            p.style.setProperty('--r', Math.round(Math.random() * 720 - 360) + 'deg');
            p.style.setProperty('--t', (2200 + Math.random() * 1400) + 'ms');
            p.style.setProperty('--d', (slide.classList.contains('wr-grade') ? 550 : 350) + Math.random() * 180 + 'ms');
            box.appendChild(p);
        }
        slide.appendChild(box);
        setTimeout(function () { box.remove(); }, 4400);
    }
    function paint() {
        bars.forEach(function (b, i) {
            b.style.animation = 'none'; b.style.transform = i < index ? 'scaleX(1)' : 'scaleX(0)';
        });
        if (!paused && index < slides.length - 1) {
            var bar = bars[index]; void bar.offsetWidth;
            bar.style.transform = ''; bar.style.animation = 'wr-fill ' + (DURATION - elapsed) + 'ms linear forwards';
        } else if (index === slides.length - 1) { bars[index].style.transform = 'scaleX(1)'; }
    }
    function schedule() {
        clearTimeout(timer);
        if (paused || index >= slides.length - 1) return;
        started = Date.now();
        timer = setTimeout(function () { go(index + 1); }, DURATION - elapsed);
    }
    function go(i) {
        if (i < 0 || i >= slides.length) return;
        slides[index].classList.remove('is-active'); slides[index].hidden = true;
        index = i; elapsed = 0;
        slides[index].hidden = false; slides[index].classList.add('is-active');
        countUp(slides[index]); burst(slides[index]); paint(); schedule();
    }
    function togglePause() {
        paused = !paused;
        if (paused) { clearTimeout(timer); elapsed += Date.now() - started; } else { started = Date.now(); }
        pauseBtn.innerHTML = paused ? PLAY : PAUSE;
        pauseBtn.setAttribute('aria-label', paused ? 'Lanjutkan' : 'Jeda');
        paint(); schedule();
    }

    root.querySelector('[data-wr-next]').addEventListener('click', function () { go(index + 1); });
    root.querySelector('[data-wr-prev]').addEventListener('click', function () { go(index - 1); });
    pauseBtn.addEventListener('click', togglePause);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') go(index + 1);
        else if (e.key === 'ArrowLeft') go(index - 1);
        else if (e.key === ' ' && e.target === document.body) { e.preventDefault(); togglePause(); }
        else if (e.key === 'Escape') window.location.href = root.querySelector('.wr-top a').href;
    });
    document.addEventListener('visibilitychange', function () { if (document.hidden && !paused) togglePause(); });
    var restart = root.querySelector('[data-wr-restart]');
    if (restart) restart.addEventListener('click', function () { if (paused && !reduce) togglePause(); go(0); });

    // Kartu ringkasan sebagai gambar PNG (digambar di canvas, tanpa pustaka luar)
    var save = root.querySelector('[data-wr-save]');
    if (save) save.addEventListener('click', function () {
        var d = JSON.parse(root.querySelector('[data-wr-data]').textContent);
        var c = document.createElement('canvas'); c.width = 1080; c.height = 1350;
        var x = c.getContext('2d');
        var draw = function () {
            var g = x.createLinearGradient(0, 0, 1080, 1350);
            g.addColorStop(0, '#03423e'); g.addColorStop(0.55, '#037a68'); g.addColorStop(1, '#02a86f');
            x.fillStyle = g; x.fillRect(0, 0, 1080, 1350);
            x.fillStyle = 'rgba(255,255,255,0.08)'; x.beginPath(); x.arc(940, 160, 260, 0, Math.PI * 2); x.fill();
            x.fillStyle = '#a7f3d0'; x.font = '700 40px Geist, sans-serif'; x.fillText('KP WRAPPED ' + d.year, 90, 170);
            x.fillStyle = '#fff'; x.font = '800 84px Geist, sans-serif'; wrap(d.name, 90, 290, 900, 92);
            x.fillStyle = 'rgba(255,255,255,0.82)'; x.font = '500 36px Geist, sans-serif'; wrap(d.place, 90, 470, 900, 46);
            var items = [['Hari tercatat', d.days], ['Task selesai', d.done + '/' + d.tasks], ['Goal tercapai', d.goals], [d.lastLabel, d.last]];
            items.forEach(function (it, i) {
                var cx = 90 + (i % 2) * 460, cy = 640 + Math.floor(i / 2) * 260;
                x.fillStyle = 'rgba(255,255,255,0.12)'; roundRect(cx, cy, 420, 220, 32); x.fill();
                x.fillStyle = 'rgba(255,255,255,0.75)'; x.font = '500 32px Geist, sans-serif'; x.fillText(it[0], cx + 40, cy + 70);
                x.fillStyle = '#fff'; x.font = '800 92px Geist, sans-serif'; x.fillText(String(it[1]), cx + 40, cy + 175);
            });
            x.fillStyle = 'rgba(255,255,255,0.7)'; x.font = '600 30px Geist, sans-serif'; x.fillText('SIMAGTI · Sistem Monitoring Magang Terintegrasi', 90, 1260);
            var a = document.createElement('a'); a.download = 'kp-wrapped-' + d.year + '.png'; a.href = c.toDataURL('image/png'); document.body.appendChild(a); a.click(); a.remove();
        };
        function roundRect(px, py, w, h, r) { x.beginPath(); x.moveTo(px + r, py); x.arcTo(px + w, py, px + w, py + h, r); x.arcTo(px + w, py + h, px, py + h, r); x.arcTo(px, py + h, px, py, r); x.arcTo(px, py, px + w, py, r); x.closePath(); }
        function wrap(text, px, py, max, lh) {
            var words = String(text).split(' '), line = '';
            words.forEach(function (wd) { var t = line ? line + ' ' + wd : wd; if (x.measureText(t).width > max && line) { x.fillText(line, px, py); line = wd; py += lh; } else { line = t; } });
            x.fillText(line, px, py);
        }
        (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(draw);
    });

    if (reduce) { pauseBtn.innerHTML = PLAY; pauseBtn.setAttribute('aria-label', 'Lanjutkan'); }
    countUp(slides[0]); paint(); schedule();
})();
</script>
</body>
</html>
