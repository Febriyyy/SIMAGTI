@php
    $student = $internship->student;
    $template = $certificate->template;
    $issued = ($certificate->issued_at ?? now())->locale('id');
    $start = $internship->start_date->locale('id');
    $end = $internship->end_date->locale('id');
    $months = max(1, (int) round($internship->start_date->diffInDays($internship->end_date) / 30));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sertifikat KP {{ $student->name }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <style>
        /*
         * Sertifikat KP, A4 mendatar, dua kolom:
         * pita hijau (nomor, nilai, verifikasi QR) | lembar putih (logo, nama, keterangan, dua tanda tangan).
         * Warna mengikuti token merek di public/css/kp.css (hijau utama, lime, gelap).
         */
        :root {
            --ink: #03423e; --primary: #037a68; --deep: #02362f; --lime: #c9f065;
            --text: #142b29; --muted: #5d706d; --line: #d9e3e0; --page: #e6eeec; --paper: #ffffff; --tint: #f3f8f5;
        }
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { background: var(--page); color: var(--text); font-family: "Geist", system-ui, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; max-width: 297mm; margin: 0 auto; padding: 16px; }
        .toolbar p { flex: 1 1 240px; margin: 0; color: var(--muted); font-size: 14px; }
        .toolbar a, .toolbar button { min-height: 40px; padding: 8px 16px; border: 1px solid var(--line); border-radius: 999px; background: #fff; color: var(--primary); font: 600 14px/1.4 inherit; text-decoration: none; cursor: pointer; }
        .toolbar button { border-color: var(--primary); background: var(--primary); color: #fff; }
        .toolbar a:focus-visible, .toolbar button:focus-visible { outline: 3px solid var(--lime); outline-offset: 2px; }

        .sheet-wrap { overflow-x: auto; padding: 0 16px 32px; }
        .sheet {
            position: relative; display: grid; grid-template-columns: 82mm 1fr;
            width: 297mm; height: 210mm; margin: 0 auto; overflow: hidden;
            background: var(--paper); box-shadow: 0 12px 40px rgba(3, 66, 62, .16);
        }

        /* ---------- Pita kiri ---------- */
        .band { position: relative; display: flex; flex-direction: column; justify-content: space-between; padding: 16mm 12mm 14mm; overflow: hidden; color: #fff;
            background: linear-gradient(165deg, #0b8f78 0%, var(--primary) 38%, var(--ink) 78%, var(--deep) 100%); }
        /* Pola garis halus seperti pengaman dokumen resmi */
        .band-pattern { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .16; pointer-events: none; }
        .band > *:not(.band-pattern) { position: relative; }
        .band-label { display: block; font-size: 3mm; color: rgba(255, 255, 255, .7); }
        .band-number { display: block; margin-top: 1mm; font-size: 4.4mm; font-weight: 700; letter-spacing: .02em; font-variant-numeric: tabular-nums; }

        /* Nilai akhir: murni tipografi. Huruf mutu besar, garis lime pendek, angka, keterangan. Tanpa kotak. */
        .grade { display: flex; flex-direction: column; align-items: flex-start; }
        .grade-label { font-size: 3mm; color: rgba(255, 255, 255, .68); }
        .grade-letter { margin-top: 2mm; font-size: 30mm; font-weight: 500; line-height: .8; letter-spacing: -.06em; color: #fff; }
        .grade-rule { width: 12mm; height: .5mm; margin: 6mm 0 4mm; background: var(--lime); }
        .grade-score { font-size: 5mm; font-weight: 600; letter-spacing: -.01em; color: #fff; font-variant-numeric: tabular-nums; }
        .grade-score small { margin-left: 1.2mm; font-size: 3mm; font-weight: 400; color: rgba(255, 255, 255, .62); }
        .grade-note { margin: 1.6mm 0 0; font-size: 3mm; line-height: 1.45; color: rgba(255, 255, 255, .68); }

        .verify { display: flex; align-items: flex-end; gap: 3.5mm; }
        .verify .qr { flex: none; display: block; width: 24mm; height: 24mm; padding: 1.8mm; border-radius: 2.5mm; background: #fff; }
        .verify .qr svg { display: block; width: 100%; height: 100%; }
        .verify .qr svg path, .verify .qr svg rect { fill: var(--ink); }
        .verify-text { min-width: 0; font-size: 2.6mm; line-height: 1.45; color: rgba(255, 255, 255, .72); }
        .verify-text strong { display: block; margin-bottom: .8mm; color: #fff; font-size: 3.2mm; }
        .verify-text span { display: block; overflow-wrap: anywhere; }

        /* ---------- Lembar kanan ---------- */
        .main { position: relative; display: grid; grid-template-rows: auto 1fr auto; padding: 14mm 18mm 13mm 18mm; }
        .main::before { content: ""; position: absolute; top: 0; left: 0; width: 1.4mm; height: 100%; background: var(--lime); }

        .head { display: flex; align-items: center; justify-content: space-between; gap: 8mm; padding-bottom: 6mm; border-bottom: .3mm solid var(--line); }
        .head-logos { display: flex; align-items: center; gap: 3.5mm; min-width: 0; }
        .head-logos img { display: block; height: 15mm; max-width: 40mm; object-fit: contain; }
        .head-logos .divider { flex: none; width: .3mm; height: 12mm; background: var(--line); }
        /* Logo yang belum diunggah: kotak seukuran logo. Hanya tampil di layar, tidak ikut tercetak. */
        .logo-slot { flex: none; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .6mm;
            width: 22mm; height: 15mm; border: .35mm dashed #b7c7c2; border-radius: 2.5mm; background: var(--tint);
            color: var(--muted); font-size: 2.5mm; line-height: 1.2; text-align: center; }
        .logo-slot b { color: var(--primary); font-size: 2.8mm; font-weight: 650; }
        /* Identitas kampus: nama universitas, lalu fakultas | prodi dalam satu baris */
        .head-campus { flex: none; display: flex; flex-direction: column; align-items: flex-end; gap: 1.4mm; text-align: right; }
        .head-campus strong { color: var(--ink); font-size: 4mm; font-weight: 700; line-height: 1.2; white-space: nowrap; }
        .head-campus span { display: flex; align-items: center; gap: 2.2mm; color: var(--muted); font-size: 2.8mm; line-height: 1.3; white-space: nowrap; }
        .head-campus span i { width: .3mm; height: 3mm; background: var(--line); }

        .body { align-self: center; padding: 4mm 0; }
        .title { margin: 0; color: var(--ink); font-size: 8.4mm; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; }
        .title span { display: block; font-size: 4mm; font-weight: 500; letter-spacing: 0; color: var(--muted); margin-bottom: 1.5mm; }
        .given { margin: 8mm 0 0; color: var(--muted); font-size: 3.6mm; }
        .name { margin: 1.5mm 0 0; color: var(--text); font-size: 12.5mm; font-weight: 700; letter-spacing: -.03em; line-height: 1.05; }
        .name-bar { display: block; width: 26mm; height: 1.4mm; margin-top: 4mm; border-radius: 1mm; background: var(--lime); }
        .nim { margin: 3mm 0 0; color: var(--muted); font-size: 3.4mm; font-variant-numeric: tabular-nums; }
        .desc { max-width: 165mm; margin: 6mm 0 0; font-size: 3.9mm; line-height: 1.65; color: var(--text); }
        .desc strong { color: var(--ink); }

        .foot { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 12mm; align-items: end; }
        .foot .date { grid-column: 1 / -1; margin-bottom: 1mm; font-size: 3.3mm; color: var(--muted); }
        .sign { font-size: 3.2mm; }
        .sign .role { color: var(--muted); }
        .sign .space { height: 17mm; }
        .sign .space.signed { display: flex; align-items: flex-end; }
        .sign .space.signed img { display: block; max-width: 46mm; max-height: 16mm; object-fit: contain; mix-blend-mode: multiply; }
        .sign strong { display: inline-block; min-width: 46mm; max-width: 100%; padding-top: 1.6mm; border-top: .3mm solid var(--text); font-size: 3.5mm; font-weight: 650; }
        .sign small { display: block; margin-top: .6mm; color: var(--muted); font-size: 2.9mm; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet-wrap { padding: 0; overflow: visible; }
            .sheet { box-shadow: none; }
            .logo-slot { display: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <p>Pilih <strong>Simpan sebagai PDF</strong> pada jendela cetak untuk mendapatkan berkas PDF. Atur ukuran kertas A4, orientasi mendatar.</p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}">Kembali</a>
    <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<div class="sheet-wrap">
    <article class="sheet" aria-label="Sertifikat Kerja Praktik">
        {{-- Pita kiri: nomor, nilai, verifikasi --}}
        <aside class="band">
            <svg class="band-pattern" viewBox="0 0 310 794" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <g fill="none" stroke="#c9f065" stroke-width="1">
                    @for ($r = 40; $r <= 760; $r += 18)
                        <circle cx="-40" cy="840" r="{{ $r }}" />
                    @endfor
                </g>
            </svg>

            <div>
                <span class="band-label">Nomor sertifikat</span>
                <span class="band-number">{{ $certificate->number }}</span>
            </div>

            <div class="grade">
                <span class="grade-label">Nilai akhir</span>
                @if ($internship->final_grade)
                    <b class="grade-letter">{{ $internship->final_grade }}</b>
                @endif
                <span class="grade-rule" aria-hidden="true"></span>
                @if ($internship->final_score !== null)
                    <span class="grade-score">{{ \App\Support\Num::short((float) $internship->final_score) }}<small>dari 100</small></span>
                @endif
                <p class="grade-note">Kerja Praktik dinyatakan diterima</p>
            </div>

            <div class="verify">
                <a class="qr" href="{{ $verifyUrl }}" aria-label="Kode QR verifikasi sertifikat, buka halaman verifikasi">{!! $qr !!}</a>
                <div class="verify-text">
                    <strong>Pindai untuk verifikasi</strong>
                    <span>Keaslian sertifikat bisa dicek di {{ parse_url($verifyUrl, PHP_URL_HOST) }}</span>
                </div>
            </div>
        </aside>

        {{-- Lembar kanan --}}
        <section class="main">
            <header class="head">
                <div class="head-logos">
                    @if ($campusLogo)
                        <img src="{{ route('logo.kampus') }}?v={{ $campusLogo->id }}" alt="Logo {{ $campus['university'] }}">
                    @else
                        <span class="logo-slot"><b>Unggah logo</b>kampus</span>
                    @endif
                    @if ($facultyLogo)
                        <img src="{{ route('logo.fakultas') }}?v={{ $facultyLogo->id }}" alt="Logo {{ $campus['faculty'] }}">
                    @else
                        <span class="logo-slot"><b>Unggah logo</b>fakultas</span>
                    @endif
                    <span class="divider" aria-hidden="true"></span>
                    @if ($internship->company?->logo_file_id)
                        <img src="{{ route('logo.perusahaan', $internship->company) }}?v={{ $internship->company->logo_file_id }}" alt="Logo {{ $internship->company->name }}">
                    @else
                        <span class="logo-slot"><b>Unggah logo</b>perusahaan</span>
                    @endif
                </div>
                <div class="head-campus">
                    <strong>{{ $campus['university'] }}</strong>
                    <span>{{ $campus['faculty'] }}<i aria-hidden="true"></i>{{ $campus['program'] }}</span>
                </div>
            </header>

            <div class="body">
                <h1 class="title"><span>Sertifikat</span>Kerja Praktik</h1>

                <p class="given">Diberikan kepada</p>
                <p class="name">{{ $student->name }}</p>
                <span class="name-bar" aria-hidden="true"></span>
                @if ($student->nim)<p class="nim">NIM {{ $student->nim }}</p>@endif

                <p class="desc">
                    atas penyelesaian Kerja Praktik
                    @if ($internship->position) sebagai <strong>{{ $internship->position }}</strong>@endif
                    di <strong>{{ $internship->placeName() }}</strong>
                    selama {{ $months }} bulan, {{ $start->translatedFormat('j F Y') }} sampai {{ $end->translatedFormat('j F Y') }}.
                </p>
            </div>

            <footer class="foot">
                <p class="date">{{ $campus['city'] }}, {{ $issued->translatedFormat('j F Y') }}</p>
                <div class="sign">
                    <div class="role">{{ $internship->division ? 'Ketua Divisi '.$internship->division->name : 'Pimpinan perusahaan' }}</div>
                    @if ($divisionSignature)
                        <div class="space signed"><img src="{{ $divisionSignature }}" alt="Tanda tangan ketua divisi"></div>
                    @else
                        <div class="space" aria-hidden="true"></div>
                    @endif
                    <strong>{{ $internship->division?->head_name ?? '……………………………' }}</strong>
                    <small>{{ $internship->company?->name }}</small>
                </div>
                <div class="sign">
                    <div class="role">{{ $template?->signer_title ?? 'Ketua Program Studi' }}</div>
                    @if ($kaprodiSignature)
                        <div class="space signed"><img src="{{ $kaprodiSignature }}" alt="Tanda tangan {{ $template?->signer_title ?? 'Ketua Program Studi' }}"></div>
                    @else
                        <div class="space" aria-hidden="true"></div>
                    @endif
                    <strong>{{ $template?->signer_name ?? '……………………………' }}</strong>
                    <small>{{ $campus['program'] }}</small>
                </div>
            </footer>
        </section>
    </article>
</div>
</body>
</html>
