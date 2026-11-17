<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMAGTI · Sistem Monitoring Magang Terintegrasi</title>
    <meta name="description" content="SIMAGTI memantau magang mahasiswa dari pengajuan, task harian, bimbingan, penilaian, sampai sertifikat dalam satu tempat.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
    <script>
        // Animasi pembuka hanya bila halaman terlihat saat dimuat dan pengguna tidak meminta gerak dikurangi.
        (function () {
            var still = false;
            try { still = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
            if (!still && document.visibilityState === 'visible') document.documentElement.classList.add('motion');
        })();
    </script>
</head>
<body class="ld">
<?php
    $icon = fn (string $d) => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    $num = fn (int $n) => number_format($n, 0, ',', '.');
?>

<a href="#isi" class="ld-skip">Lewati ke isi</a>

<header class="ld-nav">
    <a href="{{ route('home') }}" class="ld-brand" aria-label="SIMAGTI, beranda">
        <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="124" height="38">
    </a>
    <nav class="ld-links" aria-label="Bagian halaman">
        <a href="#peran">Peran</a>
        <a href="#alur">Alur KP</a>
        @if ($postings->isNotEmpty())<a href="#lowongan">Lowongan</a>@endif
    </nav>
    <a href="{{ route('login') }}" class="ld-btn ld-btn-dark">Masuk</a>
</header>

<main id="isi">
    {{-- Hero: judul besar di panel terang, tiga kartu angka menumpang di tepi bawahnya --}}
    <section class="ld-hero" aria-labelledby="ld-hero-title">
        <div class="ld-hero-panel">
            <svg class="ld-hero-spark" viewBox="0 0 40 40" aria-hidden="true"><path d="M20 2c1.2 10.6 5.4 15.8 18 18-12.6 2.2-16.8 7.4-18 18-1.2-10.6-5.4-15.8-18-18 12.6-2.2 16.8-7.4 18-18Z"/></svg>
            <svg class="ld-hero-star" viewBox="0 0 40 40" aria-hidden="true"><g stroke="currentColor" stroke-width="5" stroke-linecap="round"><path d="M20 4v32M4 20h32M8.7 8.7l22.6 22.6M31.3 8.7 8.7 31.3"/></g></svg>

            <h1 id="ld-hero-title" class="ld-hero-title">
                Magang terpantau
                <span class="ld-pill-arrow" aria-hidden="true"><svg viewBox="0 0 64 16"><path d="M2 8h56M50 2l8 6-8 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                dari pengajuan sampai sertifikat.
            </h1>
            <p class="ld-hero-sub">Mahasiswa, perusahaan, dosen, dan kampus bekerja di satu tempat. Setiap task, bimbingan, dan nilai tercatat rapi.</p>
            <div class="ld-hero-cta">
                <a href="{{ route('login') }}" class="ld-btn ld-btn-lime">Masuk ke SIMAGTI</a>
                <a href="#alur" class="ld-btn ld-btn-ghost">Lihat alur KP</a>
            </div>
        </div>

        <div class="ld-stats">
            <article class="ld-stat ld-stat-lime">
                <div class="ld-stat-faces" aria-hidden="true"><i>NA</i><i>RP</i><i>DS</i><i>+</i></div>
                <p class="ld-stat-num">{{ $num($stats['interns']) }}</p>
                <p class="ld-stat-label">Mahasiswa magang tercatat</p>
            </article>
            <article class="ld-stat ld-stat-dark">
                <svg class="ld-stat-wave" viewBox="0 0 220 90" aria-hidden="true"><path d="M6 70 C 30 70, 34 18, 62 18 S 92 72, 118 72 S 150 18, 176 18 S 206 52, 214 52" fill="none" stroke="currentColor" stroke-width="16" stroke-linecap="round"/></svg>
                <p class="ld-stat-num">{{ $num($stats['companies']) }}</p>
                <p class="ld-stat-label">Perusahaan mitra kampus</p>
            </article>
            <article class="ld-stat ld-stat-green">
                <svg class="ld-stat-bars" viewBox="0 0 120 60" aria-hidden="true">
                    @foreach ([22, 34, 28, 44, 38, 52] as $n => $h)
                        <rect x="{{ $n * 20 }}" y="{{ 60 - $h }}" width="12" height="{{ $h }}" rx="6"/>
                    @endforeach
                </svg>
                <p class="ld-stat-num">{{ $num($stats['tasks']) }}</p>
                <p class="ld-stat-label">Task dikerjakan dan dipantau</p>
                @if ($postings->isNotEmpty())
                    <a href="#lowongan" class="ld-stat-notch">Lihat lowongan</a>
                @endif
            </article>
        </div>
    </section>

    {{-- Peran: kartu per peran, masing-masing dengan cuplikan layar yang ia pakai --}}
    <section id="peran" class="ld-section" aria-labelledby="ld-roles-title">
        <div class="ld-roles">
            <div class="ld-head">
                <h2 id="ld-roles-title" class="ld-h2">Satu sistem untuk setiap peran.</h2>
                <p class="ld-head-note">Setiap orang hanya melihat yang perlu ia kerjakan, tanpa berpindah aplikasi.</p>
            </div>

            <div class="ld-role-grid">
                <article class="ld-role">
                    <header class="ld-role-head"><span class="ld-role-icon">{!! $icon('<path d="M3 9.5 12 5l9 4.5-9 4.5-9-4.5Z"/><path d="M7 11.5V16c0 1.2 2.2 2.5 5 2.5s5-1.3 5-2.5v-4.5"/>') !!}</span><h3>Mahasiswa</h3></header>
                    <p>Rencana, task harian, dan laporan per bab.</p>
                    <div class="ld-mock ld-mock-tasks" aria-hidden="true">
                        <span class="is-done"><i></i>Rancang alur pendaftaran</span>
                        <span class="is-done"><i></i>Catatan harian minggu 3</span>
                        <span><i></i>Bab 2 laporan akhir</span>
                    </div>
                </article>

                <article class="ld-role ld-role-fill">
                    <header class="ld-role-head"><span class="ld-role-icon">{!! $icon('<rect x="3.5" y="7.5" width="17" height="12" rx="2.5"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5M3.5 12.5h17"/>') !!}</span><h3>Company Supervisor</h3></header>
                    <p>Memantau progres dan menilai di akhir magang.</p>
                    <div class="ld-mock ld-mock-rate" aria-hidden="true">
                        <b>Komunikasi tim</b>
                        <div class="ld-rate"><i class="is-on"></i><i class="is-on"></i><i class="is-on"></i><i class="is-on"></i><i class="is-on"></i></div>
                        <small>Sangat baik</small>
                    </div>
                </article>

                <article class="ld-role">
                    <header class="ld-role-head"><span class="ld-role-icon">{!! $icon('<path d="M5 5.5h9a3 3 0 0 1 3 3V19a2.5 2.5 0 0 0-2.5-2.5H5Z"/><path d="M19 5.5v13.5M8.5 9.5h5M8.5 12.5h3"/>') !!}</span><h3>Dosen pembimbing</h3></header>
                    <p>Bimbingan, validasi nilai, dan catatan sidang.</p>
                    <div class="ld-mock ld-mock-note" aria-hidden="true">
                        <span class="ld-note-in">Bab 2 sudah saya unggah, Pak.</span>
                        <span class="ld-note-out">Bagus, perjelas metode di 2.3.</span>
                    </div>
                </article>

                <article class="ld-role">
                    <header class="ld-role-head"><span class="ld-role-icon">{!! $icon('<path d="M4.5 19.5h15M7 16V11M12 16V7M17 16v-3"/>') !!}</span><h3>Kaprodi dan TU</h3></header>
                    <p>Persetujuan, jadwal sidang, dan rekap prodi.</p>
                    <div class="ld-mock ld-mock-chart" aria-hidden="true">
                        @foreach ([['Berjalan', 72], ['Selesai', 48], ['Diajukan', 26]] as [$label, $w])
                            <span><small>{{ $label }}</small><i style="--w: {{ $w }}%"></i></span>
                        @endforeach
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- Alur KP: pita lime, kartu progres gelap di kiri, empat tahap di kanan --}}
    <section id="alur" class="ld-flow" aria-labelledby="ld-flow-title">
        <div class="ld-flow-inner">
            <div class="ld-progress" aria-hidden="true">
                <div class="ld-progress-top">
                    <span class="ld-progress-who"><i>NA</i><span><b>Nabila Azzahra</b><small>PT Arunika Data</small></span></span>
                    <span class="ld-progress-week">Minggu 7 dari 12</span>
                </div>
                <div class="ld-progress-main">
                    <svg class="ld-ring" viewBox="0 0 120 120"><circle cx="60" cy="60" r="50"/><circle class="ld-ring-val" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="68 100"/></svg>
                    <div>
                        <p class="ld-progress-num">68%</p>
                        <p class="ld-progress-label">Progres KP</p>
                    </div>
                </div>
                <div class="ld-progress-rows">
                    <span><small>Task selesai</small><b>24 dari 31</b></span>
                    <span><small>Bimbingan</small><b>6 sesi</b></span>
                    <span><small>Laporan</small><b>3 dari 5 bab</b></span>
                </div>
            </div>

            <div class="ld-steps-wrap">
                <h2 id="ld-flow-title" class="ld-h2">Empat tahap, semuanya tercatat.</h2>
                <ol class="ld-steps">
                    <li><span class="ld-step-n">1</span><div><b>Pengajuan KP</b><p>Pilih lowongan mitra atau ajukan tempat sendiri, lalu tunggu persetujuan kaprodi.</p></div></li>
                    <li><span class="ld-step-n">2</span><div><b>Task dan catatan harian</b><p>Kerjakan task dari supervisor dan catat kegiatan setiap hari.</p></div></li>
                    <li><span class="ld-step-n">3</span><div><b>Bimbingan dan penilaian</b><p>Dosen membimbing, supervisor menilai, kaprodi menjadwalkan sidang.</p></div></li>
                    <li><span class="ld-step-n">4</span><div><b>Sertifikat</b><p>Terbit otomatis setelah hasil KP diputuskan.</p></div></li>
                </ol>
            </div>
        </div>
    </section>

    @if ($postings->isNotEmpty())
        {{-- Lowongan terbaru dari perusahaan mitra --}}
        <section id="lowongan" class="ld-section" aria-labelledby="ld-jobs-title">
            <div class="ld-head">
                <h2 id="ld-jobs-title" class="ld-h2">Lowongan dari perusahaan mitra.</h2>
                <p class="ld-head-note">{{ $openCount }} lowongan sedang dibuka. Masuk sebagai mahasiswa untuk mengajukan KP dengan data perusahaan terisi.</p>
            </div>
            <div class="ld-jobs">
                @foreach ($postings as $p)
                    <?php
                        $co = $p->company->name;
                        $words = collect(preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|Yayasan)\.?\s+/i', '', $co))))->filter();
                        $initials = mb_strtoupper($words->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
                    ?>
                    <article class="ld-job ld-job-{{ $loop->index % 3 }}">
                        <div class="ld-job-top">
                            <span class="ld-job-mark" aria-hidden="true">{{ $initials }}</span>
                            @if ($p->deadline)<span class="ld-job-due">Tutup {{ $p->deadline->locale('id')->translatedFormat('j M') }}</span>@endif
                        </div>
                        <p class="ld-job-co">{{ $co }}</p>
                        <h3 class="ld-job-title">{{ $p->title }}</h3>
                        <p class="ld-job-meta">{{ $p->location ?: ($p->company->city ?: 'Lokasi menyusul') }}@if ($p->quota), {{ $p->quota }} orang @endif</p>
                    </article>
                @endforeach
            </div>
            <a href="{{ route('login') }}" class="ld-btn ld-btn-dark ld-jobs-cta">Masuk untuk mengajukan</a>
        </section>
    @endif

    {{-- Penutup --}}
    <section class="ld-close" aria-labelledby="ld-close-title">
        <svg class="ld-close-wave" viewBox="0 0 220 90" aria-hidden="true"><path d="M6 70 C 30 70, 34 18, 62 18 S 92 72, 118 72 S 150 18, 176 18 S 206 52, 214 52" fill="none" stroke="currentColor" stroke-width="16" stroke-linecap="round"/></svg>
        <h2 id="ld-close-title" class="ld-close-title">Kelola magang dengan lebih tenang, mulai hari ini.</h2>
        <p class="ld-close-sub">Akun dibuat oleh admin kampus atau lewat undangan perusahaan.</p>
        <a href="{{ route('login') }}" class="ld-btn ld-btn-lime">Masuk ke SIMAGTI</a>
    </section>
</main>

<footer class="ld-foot">
    <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="104" height="32">
    <p>Sistem Monitoring Magang Terintegrasi</p>
    <p class="ld-foot-year">© {{ now()->year }}</p>
</footer>
</body>
</html>
