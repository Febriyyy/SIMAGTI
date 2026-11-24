<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f7f2">
    <title>SIMAGTI · Sistem Monitoring Magang Terintegrasi</title>
    <meta name="description" content="SIMAGTI memantau magang mahasiswa dari pengajuan, task harian, bimbingan, penilaian, sampai sertifikat dalam satu tempat.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="preload" as="image" href="{{ asset('images/maskot/melambai.webp') }}" fetchpriority="high">
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
{{--
    Halaman depan SIMAGTI dengan maskot beruang berjas kotak-kotak.
    Urutan: hero (maskot melambai di panggung hijau), pita angka, alasan, empat peran (tab), alur KP, lowongan, FAQ, ajakan masuk (maskot menunjuk), kaki.
    Variabel: stats (interns, companies, tasks), postings, openCount
--}}
<body class="lp">
<?php
    $num = fn (int $n) => number_format($n, 0, ',', '.');
    $roles = [
        'mahasiswa' => ['Mahasiswa', [
            ['Rencana & task', 'Task harian dari supervisor, dikerjakan di papan kegiatan.', '24/31', 'task selesai'],
            ['Catatan harian', 'Timesheet tercatat per hari, tanpa formulir kertas.', '68', 'hari tercatat'],
            ['Laporan per bab', 'Unggah bab, revisi dari dosen langsung terlihat.', '3/4', 'bab disetujui'],
        ]],
        'supervisor' => ['Company Supervisor', [
            ['Pantau progres', 'Melihat task, catatan, dan aktivitas mahasiswa magang.', '86%', 'progres rata-rata'],
            ['Penilaian akhir', 'Satu klik per pertanyaan, skala standar kampus.', '15', 'aspek dinilai'],
            ['Data sertifikat', 'Logo perusahaan dan tanda tangan ketua divisi sekali isi.', '1×', 'untuk semua sertifikat'],
        ]],
        'dosen' => ['Dosen pembimbing', [
            ['Bimbingan', 'Jadwal, tautan rapat, dan catatan setiap sesi.', '6', 'sesi terkonfirmasi'],
            ['Laporan KP', 'Diskusi per bab dan nilai laporan akhir.', '4', 'aspek nilai laporan'],
            ['Jadwal sidang', 'Atur waktu dan penguji, mahasiswa langsung diberi tahu.', 'H-1', 'pengingat otomatis'],
        ]],
        'kampus' => ['Kaprodi & TU', [
            ['Persetujuan KP', 'Verifikasi perusahaan dan tunjuk dosen pembimbing.', '1', 'antrean hari ini'],
            ['Pengesahan nilai', 'Dosen 50% dan industri 50%, berita acara siap cetak.', '50/50', 'bobot nilai sidang'],
            ['Rekap per prodi', 'Status, nilai, dan rekrutmen, unduh ke Excel.', '.xlsx', 'sekali klik'],
        ]],
    ];
    $faqs = [
        ['Bagaimana cara mendapatkan akun?', 'Akun mahasiswa, dosen, dan kampus dibuat oleh admin. Company Supervisor menerima undangan lewat email dari TU Fakultas, lalu mengaktifkan akunnya sendiri.'],
        ['Bisakah saya mengajukan tempat magang sendiri?', 'Bisa. Pilih lowongan dari perusahaan mitra atau ajukan perusahaan sendiri. Pengajuan diverifikasi TU dan disetujui kaprodi.'],
        ['Saya lupa kata sandi. Apa yang harus dilakukan?', 'Pilih "Lupa kata sandi?" di halaman masuk. Tautan untuk membuat sandi baru dikirim ke email Anda dan berlaku 60 menit.'],
        ['Bagaimana keaslian sertifikat diperiksa?', 'Setiap sertifikat memuat kode QR. Siapa pun bisa memindainya untuk membuka halaman verifikasi SIMAGTI tanpa perlu masuk.'],
    ];
    $arrow = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    $check = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
?>

<a href="#isi" class="lp-skip">Lewati ke isi</a>

<header class="lp-nav-wrap">
    <div class="lp-nav">
        <a href="{{ route('home') }}" class="lp-brand" aria-label="SIMAGTI, beranda">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="118" height="36">
        </a>
        <nav class="lp-links" aria-label="Bagian halaman">
            <a href="#peran">Peran</a>
            <a href="#alur">Alur KP</a>
            @if ($postings->isNotEmpty())<a href="#lowongan">Lowongan</a>@endif
            <a href="#faq">FAQ</a>
        </nav>
        <a href="{{ route('login') }}" class="lp-btn lp-btn-dark lp-nav-cta">Masuk</a>
    </div>
</header>

<main id="isi">
    {{-- ============ Hero: teks kiri, maskot melambai di panggung hijau ============ --}}
    <section class="lp-hero" aria-labelledby="lp-hero-title">
        <div class="lp-wrap lp-hero-grid">
            <div class="lp-hero-copy">
                <p class="lp-pill"><span class="lp-pill-dot" aria-hidden="true"></span>Sistem Monitoring Magang Terintegrasi</p>
                <h1 id="lp-hero-title" class="lp-hero-title">
                    Magang terpantau,
                    <span class="lp-scribble">tanpa ribet.<svg viewBox="0 0 300 24" preserveAspectRatio="none" aria-hidden="true"><path d="M4 16 C 70 6, 150 4, 296 12 M 30 21 C 110 13, 190 12, 270 17"/></svg></span>
                </h1>
                <p class="lp-hero-sub">Pengajuan, task harian, bimbingan, nilai, sampai sertifikat. Mahasiswa, perusahaan, dosen, dan kampus bekerja di satu tempat.</p>
                <div class="lp-hero-cta">
                    <a href="{{ route('login') }}" class="lp-btn lp-btn-lime">Masuk ke SIMAGTI {!! $arrow !!}</a>
                    <a href="#alur" class="lp-btn lp-btn-ghost">Lihat alur KP</a>
                </div>
            </div>

            <div class="lp-stage">
                <div class="lp-stage-bg" aria-hidden="true">
                    <span class="lp-ring lp-ring-1"></span>
                    <span class="lp-ring lp-ring-2"></span>
                    <span class="lp-ring lp-ring-3"></span>
                </div>
                <img class="lp-mascot" src="{{ asset('images/maskot/melambai.webp') }}" alt="Maskot SIMAGTI, beruang berjas kotak-kotak hijau, melambai" width="703" height="900" fetchpriority="high">

                <div class="lp-float lp-float-task" aria-hidden="true">
                    <span class="lp-float-ic">{!! $check !!}</span>
                    <span><b>Task disetujui</b><small>Rancang alur pendaftaran</small></span>
                </div>
                <div class="lp-float lp-float-progress" aria-hidden="true">
                    <small>Kemajuan KP</small>
                    <b>82%</b>
                    <span class="lp-bar"><i></i></span>
                    <small>Minggu 10 dari 12</small>
                </div>
                <div class="lp-float lp-float-grade" aria-hidden="true">
                    <b>A</b>
                    <span><small>Nilai akhir</small>91 / 100</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Pita angka (data asli) ============ --}}
    <section id="angka" class="lp-wrap" aria-label="SIMAGTI dalam angka">
        <dl class="lp-stats">
            <div><dt>Mahasiswa magang</dt><dd>{{ $num($stats['interns']) }}</dd></div>
            <div><dt>Perusahaan mitra</dt><dd>{{ $num($stats['companies']) }}</dd></div>
            <div><dt>Task dipantau</dt><dd>{{ $num($stats['tasks']) }}</dd></div>
            <div><dt>Lowongan dibuka</dt><dd>{{ $num($openCount) }}</dd></div>
        </dl>
    </section>

    {{-- ============ Alasan: tiga kartu lime ============ --}}
    <section class="lp-why" aria-labelledby="lp-why-title">
        <div class="lp-wrap">
            <div class="lp-head is-center">
                <h2 id="lp-why-title" class="lp-h2">Tinggalkan logbook kertas.</h2>
                <p class="lp-lead">Semua yang dulu tercecer di map, grup chat, dan tabel kini punya tempat yang jelas.</p>
            </div>
            <div class="lp-why-grid">
                <article class="lp-why-card">
                    <span class="lp-why-ic" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="3"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span>
                    <h3>Catatan harian digital</h3>
                    <p>Task dan timesheet tercatat setiap hari, langsung terlihat oleh supervisor dan dosen.</p>
                </article>
                <article class="lp-why-card">
                    <span class="lp-why-ic" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span>
                    <h3>Nilai tidak tercecer</h3>
                    <p>Nilai supervisor, dosen, dan sidang dihitung otomatis dengan bobot setiap prodi.</p>
                </article>
                <article class="lp-why-card">
                    <span class="lp-why-ic" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg></span>
                    <h3>Sertifikat terverifikasi</h3>
                    <p>Terbit otomatis setelah KP diputuskan, lengkap dengan kode QR untuk dicek keasliannya.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ Empat peran: tab pil ============ --}}
    <section id="peran" class="lp-roles" aria-labelledby="lp-roles-title">
        <div class="lp-wrap">
            <div class="lp-head is-center">
                <h2 id="lp-roles-title" class="lp-h2">Satu sistem. Empat peran.</h2>
                <p class="lp-lead">Setiap orang hanya melihat yang perlu ia kerjakan.</p>
            </div>

            <div class="lp-tabs" role="tablist" aria-label="Peran pengguna">
                @foreach ($roles as $key => [$label])
                    <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}" class="lp-tab">{{ $label }}</button>
                @endforeach
            </div>

            @foreach ($roles as $key => [$label, $features])
                <div role="tabpanel" id="panel-{{ $key }}" aria-labelledby="tab-{{ $key }}" class="lp-feats" @unless ($loop->first) hidden @endunless>
                    @foreach ($features as [$title, $desc, $big, $unit])
                        <article class="lp-feat">
                            <p class="lp-feat-big">{{ $big }}<small>{{ $unit }}</small></p>
                            <h3>{{ $title }}</h3>
                            <p>{{ $desc }}</p>
                        </article>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ Alur KP: blok hutan gelap, empat baris tahap ============ --}}
    <section id="alur" class="lp-flow" aria-labelledby="lp-flow-title">
        <div class="lp-wrap lp-flow-grid">
            <div class="lp-flow-head">
                <h2 id="lp-flow-title" class="lp-h2">Empat tahap. Semua tercatat.</h2>
                <p class="lp-lead">Dari pengajuan sampai sertifikat, setiap langkah punya pemilik dan jejak yang jelas.</p>
                <a href="{{ route('login') }}" class="lp-btn lp-btn-lime">Mulai dari tahap 1 {!! $arrow !!}</a>
            </div>
            <ol class="lp-steps">
                @foreach ([
                    ['Pengajuan KP', 'Pilih lowongan mitra atau ajukan tempat sendiri, lalu disetujui kaprodi.', 'Mahasiswa, Kaprodi'],
                    ['Task & catatan', 'Kerjakan task dari supervisor dan catat kegiatan setiap hari.', 'Mahasiswa, Supervisor'],
                    ['Bimbingan & nilai', 'Dosen membimbing dan menjadwalkan sidang, supervisor menilai, kaprodi mengesahkan.', 'Dosen, Kaprodi'],
                    ['Sertifikat', 'Terbit otomatis setelah hasil KP diputuskan, lengkap dengan QR verifikasi.', 'Otomatis'],
                ] as $n => [$title, $desc, $who])
                    <li class="lp-step">
                        <span class="lp-step-n" aria-hidden="true">{{ $n + 1 }}</span>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $desc }}</p>
                        </div>
                        <span class="lp-step-who">{{ $who }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============ Lowongan ============ --}}
    @if ($postings->isNotEmpty())
        <section id="lowongan" class="lp-jobs-sec" aria-labelledby="lp-jobs-title">
            <div class="lp-wrap">
                <div class="lp-head is-split">
                    <h2 id="lp-jobs-title" class="lp-h2">Lowongan dari perusahaan mitra.</h2>
                    <span class="lp-count">{{ $openCount }} lowongan dibuka</span>
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
            </div>
        </section>
    @endif

    {{-- ============ FAQ ============ --}}
    <section id="faq" class="lp-faq" aria-labelledby="lp-faq-title">
        <div class="lp-wrap lp-faq-grid">
            <div class="lp-faq-side">
                <h2 id="lp-faq-title" class="lp-h2">Pertanyaan yang sering muncul.</h2>
                <div class="lp-help">
                    <h3>Masih ada yang membingungkan?</h3>
                    <p>Hubungi admin prodi atau TU fakultas di kampus Anda.</p>
                </div>
            </div>
            <div class="lp-faq-list">
                @foreach ($faqs as [$q, $a])
                    <details class="lp-qa" @if ($loop->first) open @endif>
                        <summary>{{ $q }}<span class="lp-qa-ic" aria-hidden="true"></span></summary>
                        <p>{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Ajakan masuk: maskot menunjuk ke tombol ============ --}}
    <section class="lp-wrap lp-cta-sec" aria-labelledby="lp-cta-title">
        <div class="lp-cta">
            <img class="lp-cta-mascot" src="{{ asset('images/maskot/menunjuk.webp') }}" alt="" width="748" height="900" loading="lazy">
            <div class="lp-cta-copy">
                <h2 id="lp-cta-title" class="lp-h2">Siap mulai? Masuk sekarang.</h2>
                <p>Akun dibuat oleh admin kampus atau lewat undangan dari TU fakultas.</p>
                <a href="{{ route('login') }}" class="lp-btn lp-btn-dark">Masuk ke SIMAGTI {!! $arrow !!}</a>
            </div>
        </div>
    </section>
</main>

<footer class="lp-footer">
    <div class="lp-wrap">
        <div class="lp-footer-card">
            <div class="lp-footer-brand">
                <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="118" height="36">
                <p>Sistem Monitoring Magang Terintegrasi untuk mahasiswa, perusahaan, dosen, dan kampus.</p>
            </div>
            <nav class="lp-footer-links" aria-label="Bagian halaman">
                <a href="#peran">Peran</a>
                <a href="#alur">Alur KP</a>
                @if ($postings->isNotEmpty())<a href="#lowongan">Lowongan</a>@endif
                <a href="#faq">FAQ</a>
                <a href="{{ route('login') }}">Masuk</a>
            </nav>
            <p class="lp-footer-copy">© {{ now()->year }} SIMAGTI</p>
        </div>
    </div>
</footer>

<script>
    (function () {
        // Tab peran: klik atau panah kiri/kanan
        var tabs = [].slice.call(document.querySelectorAll('.lp-tab'));
        var show = function (tab, focus) {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
            });
            if (focus) tab.focus();
        };
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { show(t); });
            t.addEventListener('keydown', function (e) {
                var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
                if (!d) return;
                e.preventDefault();
                show(tabs[(i + d + tabs.length) % tabs.length], true);
            });
        });
        // Bayangan navigasi saat digulir
        var nav = document.querySelector('.lp-nav-wrap');
        var onScroll = function () { nav.classList.toggle('is-stuck', window.scrollY > 12); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
</script>
</body>
</html>
