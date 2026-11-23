@php
    $student = $internship->student;
    $template = $certificate->template;
    $issued = ($certificate->issued_at ?? now())->locale('id');
    $start = $internship->start_date->locale('id');
    $end = $internship->end_date->locale('id');
    $score = $internship->final_score !== null ? \App\Support\Num::short((float) $internship->final_score) : null;
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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,400&family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <style>
        /*
         * Sertifikat KP, A4 mendatar, komposisi simetris:
         * logo | judul | nama (serif) | keterangan dengan nilai | tanda tangan, QR, tanda tangan.
         * Bingkai garis hijau tipis dengan aksen sudut. Warna mengikuti token merek di public/css/kp.css.
         */
        :root {
            --ink: #03423e; --primary: #037a68; --lime: #c9f065;
            --text: #1b2f2c; --muted: #667a76; --line: #cfdcd8; --page: #e6eeec; --paper: #ffffff;
            --serif: "Fraunces", Georgia, serif; --sans: "Geist", system-ui, sans-serif;
        }
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { background: var(--page); color: var(--text); font-family: var(--sans); -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; max-width: 297mm; margin: 0 auto; padding: 16px; }
        .toolbar p { flex: 1 1 240px; margin: 0; color: var(--muted); font-size: 14px; }
        .toolbar a, .toolbar button { min-height: 40px; padding: 8px 16px; border: 1px solid var(--line); border-radius: 999px; background: #fff; color: var(--primary); font: 600 14px/1.4 var(--sans); text-decoration: none; cursor: pointer; }
        .toolbar button { border-color: var(--primary); background: var(--primary); color: #fff; }
        .toolbar a:focus-visible, .toolbar button:focus-visible { outline: 3px solid var(--lime); outline-offset: 2px; }

        .sheet-wrap { overflow-x: auto; padding: 0 16px 32px; }
        .sheet { position: relative; width: 297mm; height: 210mm; margin: 0 auto; overflow: hidden; background: var(--paper); box-shadow: 0 12px 40px rgba(3, 66, 62, .16); }
        /* Cahaya hijau sangat tipis di dua sudut */
        .sheet::before { content: ""; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(circle at 0% 0%, rgba(3, 122, 104, .07), transparent 38%), radial-gradient(circle at 100% 100%, rgba(201, 240, 101, .16), transparent 34%); }

        /* Bingkai: garis tipis hijau dengan aksen sudut */
        .frame { position: absolute; inset: 9mm; border: .3mm solid var(--primary); pointer-events: none; }
        .frame::before { content: ""; position: absolute; inset: 1.6mm; border: .2mm solid var(--line); }
        .corner { position: absolute; width: 9mm; height: 9mm; border-color: var(--ink); border-style: solid; }
        .corner.tl { top: -1mm; left: -1mm; border-width: 1mm 0 0 1mm; }
        .corner.tr { top: -1mm; right: -1mm; border-width: 1mm 1mm 0 0; }
        .corner.bl { bottom: -1mm; left: -1mm; border-width: 0 0 1mm 1mm; }
        .corner.br { bottom: -1mm; right: -1mm; border-width: 0 1mm 1mm 0; }

        .content { position: absolute; inset: 17mm 24mm 15mm; display: grid; grid-template-rows: auto 1fr auto; text-align: center; }

        /* Kepala: logo di tengah, identitas kampus di bawahnya */
        .logos { display: flex; align-items: center; justify-content: center; gap: 5mm; min-height: 15mm; }
        .logos img { display: block; height: 15mm; max-width: 42mm; object-fit: contain; }
        .logos .divider { width: .3mm; height: 11mm; background: var(--line); }
        .logo-slot { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5mm; width: 22mm; height: 15mm;
            border: .35mm dashed #b7c7c2; border-radius: 2.5mm; background: #f4f8f6; color: var(--muted); font-size: 2.5mm; line-height: 1.2; }
        .logo-slot b { color: var(--primary); font-size: 2.8mm; font-weight: 600; }
        .campus { margin: 3.5mm 0 0; font-size: 3mm; color: var(--muted); letter-spacing: .01em; }
        .campus strong { color: var(--ink); font-weight: 600; }

        /* Isi */
        .body { align-self: center; }
        .title { margin: 0; font-family: var(--sans); font-size: 4.6mm; font-weight: 600; letter-spacing: .02em; color: var(--primary); }
        .number { margin: 1.5mm 0 0; font-size: 2.9mm; color: var(--muted); font-variant-numeric: tabular-nums; }
        .given { margin: 7mm 0 0; font-family: var(--serif); font-style: italic; font-size: 4.4mm; color: var(--muted); }
        .name { margin: 2mm 0 0; font-family: var(--serif); font-size: 15mm; font-weight: 600; line-height: 1.05; letter-spacing: -.01em; color: var(--ink); }
        .rule { display: flex; align-items: center; justify-content: center; gap: 2.5mm; margin: 4.5mm auto 0; }
        .rule i { width: 22mm; height: .3mm; background: var(--line); }
        .rule b { width: 2.2mm; height: 2.2mm; transform: rotate(45deg); background: var(--lime); box-shadow: 0 0 0 .5mm var(--paper), 0 0 0 .8mm var(--primary); }
        .nim { margin: 3.5mm 0 0; font-size: 3.2mm; color: var(--muted); font-variant-numeric: tabular-nums; }
        .desc { max-width: 190mm; margin: 5mm auto 0; font-size: 4mm; line-height: 1.7; color: var(--text); }
        .desc strong { color: var(--ink); font-weight: 600; }
        .desc .nowrap { white-space: nowrap; }
        /* Nilai akhir: kapsul bergaris tipis, huruf mutu serif di tengah */
        .grade-pill { display: inline-flex; align-items: stretch; margin: 5mm 0 0; border: .3mm solid var(--primary); border-radius: 999px; background: var(--paper); overflow: hidden; }
        .grade-pill > span { display: flex; align-items: center; padding: 0 5mm; }
        .grade-pill > span + span { border-left: .3mm solid var(--line); }
        .gp-label { font-size: 3mm; color: var(--muted); }
        .gp-letter { font-family: var(--serif); font-size: 8mm; font-weight: 600; line-height: 1; color: var(--ink); padding-top: 1.8mm !important; padding-bottom: 1.8mm !important;
            background: linear-gradient(180deg, rgba(201, 240, 101, .35), rgba(201, 240, 101, .12)); }
        .gp-score { gap: 1.2mm; font-size: 4.2mm; font-weight: 600; color: var(--ink); font-variant-numeric: tabular-nums; }
        .gp-score small { font-size: 2.9mm; font-weight: 400; color: var(--muted); }

        /* Kaki: tanda tangan | QR | tanda tangan */
        .foot { display: grid; grid-template-columns: 1fr 44mm 1fr; align-items: end; gap: 10mm; }
        .sign { display: flex; flex-direction: column; align-items: center; font-size: 3.1mm; }
        .sign .role { color: var(--muted); }
        .sign .space { display: flex; align-items: flex-end; justify-content: center; height: 17mm; }
        .sign .space img { display: block; max-width: 46mm; max-height: 16mm; object-fit: contain; mix-blend-mode: multiply; }
        .sign strong { display: block; min-width: 52mm; padding-top: 1.6mm; border-top: .3mm solid var(--text); font-size: 3.5mm; font-weight: 600; color: var(--text); }
        .sign small { display: block; margin-top: .6mm; color: var(--muted); font-size: 2.8mm; }
        .verify { display: flex; flex-direction: column; align-items: center; gap: 1.6mm; }
        .verify .date { font-size: 3mm; color: var(--muted); white-space: nowrap; }
        .verify .qr { display: block; width: 20mm; height: 20mm; }
        .verify .qr svg { display: block; width: 100%; height: 100%; }
        .verify .qr svg path, .verify .qr svg rect { fill: var(--ink); }
        .verify small { font-size: 2.5mm; line-height: 1.35; color: var(--muted); }

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
        <div class="frame" aria-hidden="true">
            <span class="corner tl"></span><span class="corner tr"></span><span class="corner bl"></span><span class="corner br"></span>
        </div>

        <div class="content">
            <header>
                <div class="logos">
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
                <p class="campus"><strong>{{ $campus['university'] }}</strong> · {{ $campus['faculty'] }} · {{ $campus['program'] }}</p>
            </header>

            <section class="body">
                <h1 class="title">Sertifikat Kerja Praktik</h1>
                <p class="number">Nomor {{ $certificate->number }}</p>

                <p class="given">diberikan kepada</p>
                <p class="name">{{ $student->name }}</p>
                <div class="rule" aria-hidden="true"><i></i><b></b><i></i></div>
                @if ($student->nim)<p class="nim">NIM {{ $student->nim }}</p>@endif

                <p class="desc">
                    atas penyelesaian Kerja Praktik
                    @if ($internship->position) sebagai <strong>{{ $internship->position }}</strong>@endif
                    di <strong>{{ $internship->placeName() }}</strong>
                    pada <span class="nowrap">{{ $start->translatedFormat('j F Y') }}</span> sampai <span class="nowrap">{{ $end->translatedFormat('j F Y') }}</span>.
                </p>

                @if ($internship->final_grade)
                    <p class="grade-pill" aria-label="Nilai akhir {{ $internship->final_grade }}{{ $score ? ', '.$score.' dari 100' : '' }}">
                        <span class="gp-label">Nilai akhir</span>
                        <span class="gp-letter">{{ $internship->final_grade }}</span>
                        @if ($score)<span class="gp-score">{{ $score }}<small>/ 100</small></span>@endif
                    </p>
                @endif
            </section>

            <footer class="foot">
                <div class="sign">
                    <div class="role">{{ $internship->division ? 'Ketua Divisi '.$internship->division->name : 'Pimpinan perusahaan' }}</div>
                    <div class="space">
                        @if ($divisionSignature)<img src="{{ $divisionSignature }}" alt="Tanda tangan ketua divisi">@endif
                    </div>
                    <strong>{{ $internship->division?->head_name ?? '……………………………' }}</strong>
                    <small>{{ $internship->company?->name }}</small>
                </div>

                <div class="verify">
                    <span class="date">{{ $campus['city'] }}, {{ $issued->translatedFormat('j F Y') }}</span>
                    <a class="qr" href="{{ $verifyUrl }}" aria-label="Kode QR verifikasi sertifikat, buka halaman verifikasi">{!! $qr !!}</a>
                    <small>Pindai untuk memeriksa<br>keaslian sertifikat</small>
                </div>

                <div class="sign">
                    <div class="role">{{ $template?->signer_title ?? 'Ketua Program Studi' }}</div>
                    <div class="space">
                        @if ($kaprodiSignature)<img src="{{ $kaprodiSignature }}" alt="Tanda tangan {{ $template?->signer_title ?? 'Ketua Program Studi' }}">@endif
                    </div>
                    <strong>{{ $template?->signer_name ?? '……………………………' }}</strong>
                    <small>{{ $campus['program'] }}</small>
                </div>
            </footer>
        </div>
    </article>
</div>
</body>
</html>
