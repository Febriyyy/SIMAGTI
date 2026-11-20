@php
    $student = $internship->student;
    $template = $certificate->template;
    $issued = ($certificate->issued_at ?? now())->locale('id');
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
        /* Halaman cetak berdiri sendiri (A4 mendatar). Warna mengikuti token merek di public/css/kp.css. */
        :root {
            --brand-green: #02a86f; --brand-teal: #028d80; --brand-ink: #03423e;
            --primary: #037a68; --primary-soft: #e1f3ee;
            --text: #142b29; --muted: #5d706d; --line: #d4dfdc; --page: #e6eeec;
        }
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { background: var(--page); color: var(--text); font-family: "Geist", system-ui, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; max-width: 297mm; margin: 0 auto; padding: 16px; }
        .toolbar p { flex: 1 1 240px; margin: 0; color: var(--muted); font-size: 14px; }
        .toolbar a, .toolbar button { min-height: 40px; padding: 8px 16px; border: 1px solid var(--line); border-radius: 999px; background: #fff; color: var(--primary); font: 600 14px/1.4 inherit; text-decoration: none; cursor: pointer; }
        .toolbar button { border-color: var(--primary); background: var(--primary); color: #fff; }
        .toolbar a:focus-visible, .toolbar button:focus-visible { outline: 3px solid var(--brand-green); outline-offset: 2px; }

        .sheet-wrap { overflow-x: auto; padding: 0 16px 32px; }
        .sheet {
            position: relative; width: 297mm; height: 210mm; margin: 0 auto; overflow: hidden;
            background: #fff; box-shadow: 0 12px 40px rgba(3, 66, 62, .16);
        }
        /* Bingkai berlapis dan pita gradasi merek */
        .sheet::before { content: ""; position: absolute; inset: 10mm; border: 1.2mm solid var(--brand-ink); border-radius: 4mm; }
        .sheet::after { content: ""; position: absolute; inset: 12.5mm; border: .3mm solid var(--brand-teal); border-radius: 2.5mm; }
        .ribbon { position: absolute; top: 0; bottom: 0; left: 0; width: 7mm; background: linear-gradient(180deg, var(--brand-ink), var(--primary) 50%, var(--brand-green)); }
        .leaf { position: absolute; right: -28mm; bottom: -34mm; width: 120mm; opacity: .06; }

        .content { position: absolute; inset: 20mm 24mm 18mm; display: grid; grid-template-rows: auto 1fr auto; z-index: 1; }
        .head { display: flex; align-items: center; justify-content: space-between; gap: 8mm; }
        .head-logos { display: flex; align-items: center; gap: 5mm; }
        .head-logos img { display: block; height: 16mm; max-width: 60mm; object-fit: contain; }
        .head-logos .simagti { height: 12mm; }
        .head-logos .divider { width: .3mm; height: 13mm; background: var(--line); }
        .head-campus { text-align: right; font-size: 3.2mm; line-height: 1.45; color: var(--muted); }
        .head-campus strong { display: block; color: var(--brand-ink); font-size: 3.8mm; }

        .body { align-self: center; text-align: center; }
        .eyebrow { margin: 0; color: var(--primary); font-size: 3.4mm; font-weight: 700; letter-spacing: .5em; text-transform: uppercase; }
        .title { margin: 2mm 0 0; color: var(--brand-ink); font-size: 11mm; font-weight: 800; letter-spacing: -.01em; }
        .number { margin: 1.5mm 0 0; color: var(--muted); font-size: 3.4mm; }
        .given { margin: 8mm 0 0; color: var(--muted); font-size: 3.8mm; }
        .name { display: inline-block; margin: 2mm 0 0; padding: 0 6mm 2mm; border-bottom: .5mm solid var(--brand-green); color: var(--text); font-size: 10mm; font-weight: 700; }
        .nim { margin: 2mm 0 0; color: var(--muted); font-size: 3.4mm; }
        .desc { max-width: 200mm; margin: 6mm auto 0; font-size: 4mm; line-height: 1.6; }
        .desc strong { color: var(--brand-ink); }
        .grade { display: inline-flex; align-items: center; gap: 2mm; margin-top: 4mm; padding: 1.5mm 5mm; border-radius: 999px; background: var(--primary-soft); color: var(--brand-ink); font-size: 3.6mm; font-weight: 700; }

        .foot { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: end; gap: 10mm; }
        .signs { display: grid; grid-template-columns: repeat(2, 62mm); column-gap: 10mm; }
        .signs .date { grid-column: 1 / -1; margin-bottom: 2mm; text-align: right; }
        .verify { display: flex; align-items: center; gap: 3mm; font-size: 2.8mm; line-height: 1.45; color: var(--muted); }
        .verify .qr { flex: none; width: 24mm; height: 24mm; }
        .verify .qr svg { display: block; width: 100%; height: 100%; }
        .verify .qr svg path, .verify .qr svg rect { fill: var(--brand-ink); }
        .verify strong { display: block; color: var(--text); font-size: 3.2mm; }
        .verify span { word-break: break-all; }
        .date { text-align: center; font-size: 3.4mm; color: var(--muted); }
        .sign { text-align: center; font-size: 3.4mm; }
        .sign .space { height: 16mm; }
        .sign strong { display: block; padding-top: 1.5mm; border-top: .3mm solid var(--text); }
        .sign .space.signed { display: flex; align-items: flex-end; justify-content: center; }
        .sign .space.signed img { display: block; max-width: 48mm; max-height: 16mm; object-fit: contain; mix-blend-mode: multiply; }
        .sign small { color: var(--muted); font-size: 3mm; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet-wrap { padding: 0; overflow: visible; }
            .sheet { box-shadow: none; }
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
        <span class="ribbon" aria-hidden="true"></span>
        <img class="leaf" src="{{ asset('images/logo-simagti-mark.svg') }}" alt="" aria-hidden="true">

        <div class="content">
            <header class="head">
                <div class="head-logos">
                    @if ($campusLogo)
                        <img src="{{ route('logo.kampus') }}?v={{ $campusLogo->id }}" alt="Logo {{ $campus['university'] }}">
                    @else
                        <img class="simagti" src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI">
                    @endif
                    @if ($facultyLogo)
                        <img src="{{ route('logo.fakultas') }}?v={{ $facultyLogo->id }}" alt="Logo {{ $campus['faculty'] }}">
                    @endif
                    @if ($internship->company?->logo_file_id)
                        <span class="divider" aria-hidden="true"></span>
                        <img src="{{ route('logo.perusahaan', $internship->company) }}?v={{ $internship->company->logo_file_id }}" alt="Logo {{ $internship->company->name }}">
                    @endif
                </div>
                <div class="head-campus">
                    <strong>{{ $campus['university'] }}</strong>
                    {{ $campus['faculty'] }}<br>{{ $campus['program'] }}
                </div>
            </header>

            <section class="body">
                <p class="eyebrow">Sertifikat</p>
                <h1 class="title">Kerja Praktik</h1>
                <p class="number">Nomor {{ $certificate->number }}</p>

                <p class="given">Diberikan kepada</p>
                <div class="name">{{ $student->name }}</div>
                @if ($student->nim)<p class="nim">NIM {{ $student->nim }}</p>@endif

                <p class="desc">
                    yang telah menyelesaikan Kerja Praktik
                    @if ($internship->position) sebagai <strong>{{ $internship->position }}</strong> @endif
                    di <strong>{{ $internship->placeName() }}</strong>
                    pada {{ $internship->start_date->locale('id')->translatedFormat('j F Y') }} sampai {{ $internship->end_date->locale('id')->translatedFormat('j F Y') }},
                    dan dinyatakan <strong>diterima</strong>.
                </p>
                @if ($internship->final_grade)
                    <span class="grade">Nilai akhir {{ $internship->final_grade }}</span>
                @endif
            </section>

            <footer class="foot">
                <div class="verify">
                    <div class="qr" role="img" aria-label="Kode QR verifikasi sertifikat">{!! $qr !!}</div>
                    <div>
                        <strong>Pindai untuk verifikasi</strong>
                        <span>{{ $verifyUrl }}</span>
                    </div>
                </div>
                <div class="signs">
                    <div class="date">{{ $campus['city'] }}, {{ $issued->translatedFormat('j F Y') }}</div>
                    <div class="sign">
                        <div>{{ $internship->division ? 'Ketua Divisi '.$internship->division->name : 'Pimpinan perusahaan' }}</div>
                        @if ($divisionSignature)
                            <div class="space signed"><img src="{{ $divisionSignature }}" alt="Tanda tangan ketua divisi"></div>
                        @else
                            <div class="space" aria-hidden="true"></div>
                        @endif
                        <strong>{{ $internship->division?->head_name ?? '……………………………' }}</strong>
                        <small>{{ $internship->company?->name }}</small>
                    </div>
                    <div class="sign">
                        <div>{{ $template?->signer_title ?? 'Ketua Program Studi' }}</div>
                        @if ($kaprodiSignature)
                            <div class="space signed"><img src="{{ $kaprodiSignature }}" alt="Tanda tangan {{ $template?->signer_title ?? 'Ketua Program Studi' }}"></div>
                        @else
                            <div class="space" aria-hidden="true"></div>
                        @endif
                        <strong>{{ $template?->signer_name ?? '……………………………' }}</strong>
                        <small>{{ $campus['program'] }}</small>
                    </div>
                </div>
            </footer>
        </div>
    </article>
</div>
</body>
</html>
