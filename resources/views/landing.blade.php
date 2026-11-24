<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#25b47e">
    <title>SIMAGTI · Sistem Monitoring Magang Terintegrasi</title>
    <meta name="description" content="SIMAGTI memantau magang mahasiswa dari pengajuan, task harian, bimbingan, penilaian, sampai sertifikat dalam satu tempat.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,600..800&family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
{{--
    Halaman depan SIMAGTI. Blok warna penuh per bagian, huruf display besar (Bricolage Grotesque):
    hero hijau daun, pita berjalan, "peran" hutan gelap dengan pemilih peran, "alur" mint, angka dan lowongan, penutup lime.
    Variabel: stats (interns, companies, tasks), postings, openCount
--}}
<body class="lp">
<?php
    $num = fn (int $n) => number_format($n, 0, ',', '.');
    $roles = [
        'mahasiswa' => ['Mahasiswa', 'Ajukan KP, kerjakan task', [
            ['Rencana & task', 'Task harian dari supervisor, dikerjakan di papan kegiatan.', '24/31', 'task selesai'],
            ['Catatan harian', 'Timesheet tercatat per hari, tanpa formulir kertas.', '68', 'hari tercatat'],
            ['Laporan per bab', 'Unggah bab, revisi dari dosen langsung terlihat.', '3/4', 'bab disetujui'],
        ]],
        'supervisor' => ['Company Supervisor', 'Pantau dan nilai', [
            ['Pantau progres', 'Melihat task, catatan, dan aktivitas mahasiswa magang.', '86%', 'progres rata-rata'],
            ['Penilaian akhir', 'Satu klik per pertanyaan, skala standar kampus.', '15', 'aspek dinilai'],
            ['Data sertifikat', 'Logo perusahaan dan tanda tangan ketua divisi sekali isi.', '1×', 'untuk semua sertifikat'],
        ]],
        'dosen' => ['Dosen pembimbing', 'Bimbing dan validasi', [
            ['Bimbingan', 'Jadwal, tautan rapat, dan catatan setiap sesi.', '6', 'sesi terkonfirmasi'],
            ['Laporan KP', 'Diskusi per bab dan nilai laporan akhir.', '4', 'aspek nilai laporan'],
            ['Jadwal sidang', 'Atur waktu dan penguji, mahasiswa langsung diberi tahu.', 'H-1', 'pengingat otomatis'],
        ]],
        'kampus' => ['Kaprodi & TU', 'Setujui dan sahkan', [
            ['Persetujuan KP', 'Verifikasi perusahaan dan tunjuk dosen pembimbing.', '1', 'antrean hari ini'],
            ['Pengesahan nilai', 'Dosen 50% dan industri 50%, berita acara siap cetak.', '50/50', 'bobot nilai sidang'],
            ['Rekap per prodi', 'Status, nilai, dan rekrutmen, unduh ke Excel.', '.xlsx', 'sekali klik'],
        ]],
    ];
    $arrow = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
?>

<a href="#isi" class="lp-skip">Lewati ke isi</a>

<header class="lp-nav">
    <a href="{{ route('home') }}" class="lp-brand" aria-label="SIMAGTI, beranda">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="118" height="36">
        </a>
        <nav class="lp-links" aria-label="Bagian halaman">
            <a href="#peran">Peran</a>
            <a href="#alur">Alur KP</a>
            <a href="#angka">Angka</a>
            @if ($postings->isNotEmpty())<a href="#lowongan">Lowongan</a>@endif
        </nav>
        <a href="{{ route('login') }}" class="lp-btn lp-btn-dark lp-nav-cta">Masuk</a>
</header>

<main id="isi">
    {{-- ============ Hero hijau daun ============ --}}
    <section class="lp-hero" aria-labelledby="lp-hero-title">
        <div class="lp-hero-copy">
            <p class="lp-kicker">Sistem Monitoring Magang Terintegrasi</p>
            <h1 id="lp-hero-title" class="lp-display lp-hero-title">
                <span>Magang</span>
                <span>terpantau,</span>
                <span class="is-light">tanpa ribet.</span>
            </h1>
            <p class="lp-hero-sub"><b>SIMAGTI</b> menyatukan mahasiswa, perusahaan, dosen, dan kampus. Pengajuan, task harian, bimbingan, nilai, sampai sertifikat tercatat di satu tempat.</p>
            <div class="lp-hero-cta">
                <a href="{{ route('login') }}" class="lp-btn lp-btn-dark">Masuk ke SIMAGTI {!! $arrow !!}</a>
                <a href="#alur" class="lp-btn lp-btn-line">Lihat alur KP</a>
            </div>
        </div>

        {{-- Ilustrasi: lengkung hijau tua berisi tiga kartu layar SIMAGTI --}}
        <div class="lp-hero-art" aria-hidden="true">
            <div class="lp-arch"></div>
            <div class="lp-badge">
                <svg viewBox="0 0 120 120"><defs><path id="lp-circ" d="M60 60m-46 0a46 46 0 1 1 92 0a46 46 0 1 1-92 0"/></defs>
                    <text><textPath href="#lp-circ" textLength="286" lengthAdjust="spacing">EMPAT PERAN · SATU SISTEM ·</textPath></text></svg>
                <b>4</b>
            </div>

            <div class="lp-card lp-card-progress">
                <small>Kemajuan KP</small>
                <p><b>82</b>%</p>
                <span class="lp-bar"><i style="width: 82%"></i></span>
                <em>Minggu 10 dari 12. Laporan menunggu bab 4.</em>
            </div>

            <div class="lp-card lp-card-tasks">
                <span class="lp-card-head"><i>NA</i><span><b>Nabila A.</b><small>PT Arunika Data</small></span></span>
                <ul>
                    <li class="is-done">Rancang alur pendaftaran</li>
                    <li class="is-done">Catatan harian minggu 9</li>
                    <li>Bab 4 laporan akhir</li>
                </ul>
            </div>

            <div class="lp-card lp-card-grade">
                <small>Nilai akhir</small>
                <p><b>A</b><span>91 / 100</span></p>
            </div>
            <div class="lp-shelf"></div>
        </div>
    </section>

{{-- ============ Pita berjalan ============ --}}
<div class="lp-marquee" aria-hidden="true">
    <div class="lp-marquee-track">
        @for ($n = 0; $n < 2; $n++)
            <span>Pengajuan KP</span><i></i><span>Task harian</span><i></i><span class="is-lime">Bimbingan</span><i></i>
            <span>Penilaian</span><i></i><span>Sidang KP</span><i></i><span class="is-lime">Sertifikat</span><i></i>
        @endfor
    </div>
</div>

{{-- ============ Peran: hutan gelap, pemilih peran ============ --}}
<section id="peran" class="lp-roles" aria-labelledby="lp-roles-title">
    <div class="lp-wrap">
        <div class="lp-head">
            <h2 id="lp-roles-title" class="lp-display lp-h2">Satu sistem.<br><span class="is-mint">Empat peran.</span></h2>
            <p class="lp-head-note">Setiap orang hanya melihat yang perlu ia kerjakan. Pilih peran Anda untuk melihat isinya.</p>
        </div>

        <div class="lp-roles-grid">
            <div class="lp-role-pick" role="tablist" aria-label="Peran pengguna">
                @foreach ($roles as $key => [$label, $hint])
                    <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" @class(['lp-role-opt', 'is-on' => $loop->first]) data-role="{{ $key }}">
                        <span class="lp-role-n">0{{ $loop->iteration }}</span>
                        <b>{{ $label }}</b>
                        <small>{{ $hint }}</small>
                    </button>
                @endforeach
            </div>

            <div class="lp-role-panels">
                @foreach ($roles as $key => [$label, $hint, $features])
                    <div role="tabpanel" id="panel-{{ $key }}" aria-labelledby="tab-{{ $key }}" class="lp-role-panel" @unless ($loop->first) hidden @endunless>
                        <div class="lp-role-panel-head">
                            <h3>Untuk {{ $label }}</h3>
                            <span><i></i>Fitur utama</span>
                        </div>
                        <div class="lp-feats">
                            @foreach ($features as [$title, $desc, $big, $unit])
                                <article class="lp-feat">
                                    <span class="lp-feat-n">0{{ $loop->iteration }}</span>
                                    <p class="lp-feat-big">{{ $big }}<small>{{ $unit }}</small></p>
                                    <h4>{{ $title }}</h4>
                                    <p>{{ $desc }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============ Alur KP: mint, empat kartu tahap ============ --}}
<section id="alur" class="lp-flow" aria-labelledby="lp-flow-title">
    <div class="lp-wrap">
        <div class="lp-head">
            <h2 id="lp-flow-title" class="lp-display lp-h2 is-dark">Empat tahap.<br><span class="is-green">Semua tercatat.</span></h2>
            <p class="lp-head-note is-dark">Dari pengajuan sampai sertifikat, setiap langkah punya pemilik dan jejak yang jelas.</p>
        </div>
        <ol class="lp-steps">
            @foreach ([
                ['Pengajuan KP', 'Pilih lowongan mitra atau ajukan tempat sendiri, lalu disetujui kaprodi.', 'Mahasiswa → Kaprodi'],
                ['Task & catatan', 'Kerjakan task dari supervisor dan catat kegiatan setiap hari.', 'Mahasiswa → Supervisor'],
                ['Bimbingan & nilai', 'Dosen membimbing dan menjadwalkan sidang, supervisor menilai, kaprodi mengesahkan.', 'Dosen → Kaprodi'],
                ['Sertifikat', 'Terbit otomatis setelah hasil KP diputuskan, lengkap dengan QR verifikasi.', 'Otomatis'],
            ] as $n => [$title, $desc, $who])
                <li class="lp-step lp-step-{{ $n }}">
                    <span class="lp-step-n">{{ $n + 1 }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $desc }}</p>
                    <span class="lp-step-who">{{ $who }}</span>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ============ Angka dan lowongan ============ --}}
<section id="angka" class="lp-numbers" aria-labelledby="lp-numbers-title">
    <div class="lp-wrap">
        <div class="lp-head">
            <h2 id="lp-numbers-title" class="lp-display lp-h2 is-dark">Sudah berjalan.</h2>
            <p class="lp-head-note is-dark">Angka langsung dari data SIMAGTI hari ini.</p>
        </div>
        <div class="lp-stats">
            <article class="lp-stat is-lime"><p class="lp-stat-num">{{ $num($stats['interns']) }}</p><p>Mahasiswa magang tercatat</p></article>
            <article class="lp-stat is-paper"><p class="lp-stat-num">{{ $num($stats['companies']) }}</p><p>Perusahaan mitra kampus</p></article>
            <article class="lp-stat is-forest"><p class="lp-stat-num">{{ $num($stats['tasks']) }}</p><p>Task dikerjakan dan dipantau</p></article>
        </div>

        @if ($postings->isNotEmpty())
            <div id="lowongan" class="lp-jobs-head">
                <h3 class="lp-display lp-h3">Lowongan dari perusahaan mitra.</h3>
                <span class="lp-jobs-count">{{ $openCount }} lowongan dibuka</span>
            </div>
            <div class="lp-jobs">
                @foreach ($postings as $p)
                    <?php
                        $co = $p->company->name;
                        $words = collect(preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|Yayasan)\.?\s+/i', '', $co))))->filter();
                        $initials = mb_strtoupper($words->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
                        $left = $p->deadline ? (int) today()->diffInDays($p->deadline, false) : null;
                    ?>
                    <a href="{{ route('login') }}" class="lp-job" aria-label="{{ $p->title }} di {{ $co }}. Masuk untuk mengajukan.">
                        <span class="lp-job-top">
                            <span class="lp-job-mark" aria-hidden="true">{{ $initials }}</span>
                            @if ($left !== null)
                                <span @class(['lp-job-due', 'is-soon' => $left <= 7])>{{ $left === 0 ? 'Tutup hari ini' : ($left <= 7 ? $left.' hari lagi' : 'Tutup '.$p->deadline->locale('id')->translatedFormat('j M')) }}</span>
                            @endif
                        </span>
                        <span class="lp-job-co">{{ $co }}</span>
                        <span class="lp-job-title">{{ $p->title }}</span>
                        @if ($p->skills->isNotEmpty())
                            <span class="lp-job-skills">@foreach ($p->skills->take(3) as $skill)<i>{{ $skill->name }}</i>@endforeach</span>
                        @endif
                        <span class="lp-job-foot">
                            <span>{{ $p->location ?: ($p->company->city ?: 'Lokasi menyusul') }}@if ($p->quota) · {{ $p->quota }} orang @endif</span>
                            <span class="lp-job-go" aria-hidden="true">{!! $arrow !!}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ============ Penutup ============ --}}
<section class="lp-close" aria-labelledby="lp-close-title">
    <div class="lp-close-card">
        <h2 id="lp-close-title" class="lp-display lp-close-title">Siap mulai?<br>Masuk sekarang.</h2>
        <p>Akun dibuat oleh admin kampus atau lewat undangan perusahaan.</p>
        <a href="{{ route('login') }}" class="lp-btn lp-btn-dark">Masuk ke SIMAGTI {!! $arrow !!}</a>
    </div>
</section>
</main>

<footer class="lp-footer">
    <div class="lp-wrap lp-footer-inner">
        <div class="lp-footer-brand">
            <span class="lp-brand"><img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="118" height="36"></span>
            <p>Sistem Monitoring Magang Terintegrasi untuk mahasiswa, perusahaan, dosen, dan kampus.</p>
        </div>
        <nav class="lp-footer-links" aria-label="Bagian halaman">
            <a href="#peran">Peran</a>
            <a href="#alur">Alur KP</a>
            <a href="#angka">Angka</a>
            <a href="{{ route('login') }}">Masuk</a>
        </nav>
    </div>
    <p class="lp-wrap lp-footer-copy">© {{ now()->year }} SIMAGTI</p>
</footer>

<script>
    // Pemilih peran (tab): klik atau panah kiri/kanan mengganti panel
    (function () {
        var tabs = [].slice.call(document.querySelectorAll('.lp-role-opt'));
        var show = function (tab, focus) {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-on', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
            });
            if (focus) tab.focus();
        };
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { show(t); });
            t.addEventListener('keydown', function (e) {
                var d = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : e.key === 'ArrowLeft' || e.key === 'ArrowUp' ? -1 : 0;
                if (!d) return;
                e.preventDefault();
                show(tabs[(i + d + tabs.length) % tabs.length], true);
            });
        });
        // Navigasi menempel dengan latar saat digulir
        var nav = document.querySelector('.lp-nav');
        var onScroll = function () { nav.classList.toggle('is-stuck', window.scrollY > 12); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
</script>
</body>
</html>
