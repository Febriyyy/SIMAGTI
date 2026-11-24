@php
    $student = $internship->student;
    $date = ($defense->defended_at ?? now())->locale('id');
    $schedule = $internship->defenseSchedule;
    $examiner = $schedule?->examiner ?? $internship->lecturer;
    $num = fn ($v, $d = 2) => \App\Support\Num::short((float) $v, $d);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Berita Acara Sidang KP {{ $student->name }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <style>
        /* Halaman cetak berdiri sendiri (A4 tegak). Warna mengikuti token merek di public/css/kp.css. */
        :root { --ink: #0e3b33; --primary: #2e7d16; --tint: #eef7f4; --text: #142b29; --muted: #5d706d; --line: #d4dfdc; --page: #e6eeec; --lime: #c5f04a; }
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { background: var(--page); color: var(--text); font-family: "Geist", system-ui, sans-serif; font-size: 3.3mm; line-height: 1.5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; max-width: 210mm; margin: 0 auto; padding: 16px; font-size: 14px; }
        .toolbar p { flex: 1 1 240px; margin: 0; color: var(--muted); }
        .toolbar a, .toolbar button { min-height: 40px; padding: 8px 16px; border: 1px solid var(--line); border-radius: 999px; background: #fff; color: var(--primary); font: 600 14px/1.4 inherit; text-decoration: none; cursor: pointer; }
        .toolbar button { border-color: var(--primary); background: var(--primary); color: #fff; }

        .sheet-wrap { overflow-x: auto; padding: 0 16px 32px; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 16mm 18mm 14mm; background: #fff; box-shadow: 0 12px 40px rgba(14, 59, 51, .16); }
        .sheet::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3mm; background: linear-gradient(90deg, var(--ink), var(--primary) 60%, var(--lime)); }

        .head { display: flex; align-items: center; justify-content: space-between; gap: 8mm; padding-bottom: 5mm; border-bottom: .4mm solid var(--ink); }
        .head img { height: 11mm; }
        .head-campus { text-align: right; font-size: 3mm; line-height: 1.45; color: var(--muted); }
        .head-campus strong { display: block; color: var(--ink); font-size: 3.6mm; }

        h1 { margin: 8mm 0 1mm; text-align: center; color: var(--ink); font-size: 5.4mm; font-weight: 800; letter-spacing: -.01em; }
        .sub { margin: 0; text-align: center; color: var(--muted); }
        .lead { margin: 7mm 0 4mm; }

        .data { width: 100%; border-collapse: collapse; }
        .data th { width: 46mm; padding: 1.2mm 0; text-align: left; font-weight: 500; color: var(--muted); vertical-align: top; }
        .data td { padding: 1.2mm 0; font-weight: 600; }

        h2 { margin: 7mm 0 2.5mm; color: var(--ink); font-size: 3.8mm; font-weight: 700; }
        .scores { width: 100%; border-collapse: collapse; }
        .scores th, .scores td { padding: 2mm 3mm; border-bottom: .25mm solid var(--line); text-align: left; }
        .scores thead th { background: var(--tint); color: var(--ink); font-size: 3mm; font-weight: 700; }
        .scores .num { width: 26mm; text-align: right; font-variant-numeric: tabular-nums; }
        .scores tfoot td { font-weight: 700; border-bottom: 0; }

        .result { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 6mm; margin-top: 6mm; padding: 5mm 6mm; border-radius: 3mm; background: var(--tint); }
        .result p { margin: 0; color: var(--muted); }
        .result p b { color: var(--text); }
        .result-score { display: flex; align-items: baseline; gap: 3mm; color: var(--ink); }
        .result-score strong { font-size: 10mm; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
        .result-score span { padding: 1mm 3.5mm; border-radius: 999px; background: var(--ink); color: #fff; font-size: 4.2mm; font-weight: 800; }
        .note { margin: 4mm 0 0; color: var(--muted); }

        .signs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8mm; margin-top: 12mm; text-align: center; }
        .signs .place { grid-column: 3; margin-bottom: -6mm; }
        .sign .space { height: 20mm; }
        .sign strong { display: block; padding-top: 1.5mm; border-top: .3mm solid var(--text); font-weight: 600; }
        .sign small { color: var(--muted); }
        .foot { margin-top: 10mm; color: var(--muted); font-size: 2.6mm; text-align: center; }

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
    <p>Pilih <strong>Simpan sebagai PDF</strong> pada jendela cetak untuk mendapatkan berkas PDF. Ukuran kertas A4, orientasi tegak.</p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}">Kembali</a>
    <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<div class="sheet-wrap">
    <article class="sheet" aria-label="Berita acara sidang Kerja Praktik">
        <header class="head">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI">
            <div class="head-campus">
                <strong>{{ $campus['university'] }}</strong>
                {{ $campus['faculty'] }}<br>{{ $campus['program'] }}
            </div>
        </header>

        <h1>Berita Acara Sidang Kerja Praktik</h1>
        <p class="sub">{{ $campus['program'] }}</p>

        <p class="lead">
            Pada hari {{ $date->translatedFormat('l') }}, tanggal {{ $date->translatedFormat('j F Y') }}
            @if ($schedule) pukul {{ $schedule->scheduled_at->format('H.i') }}, {{ $schedule->isOnline() ? 'secara daring' : 'di '.$schedule->place }},@endif
            telah dilaksanakan sidang Kerja Praktik untuk mahasiswa berikut.
        </p>

        <table class="data">
            <tr><th>Nama</th><td>{{ $student->name }}</td></tr>
            @if ($student->nim)<tr><th>NIM</th><td>{{ $student->nim }}</td></tr>@endif
            <tr><th>Tempat Kerja Praktik</th><td>{{ $internship->placeName() }}</td></tr>
            @if ($internship->position)<tr><th>Posisi</th><td>{{ $internship->position }}</td></tr>@endif
            <tr><th>Periode</th><td>{{ $internship->start_date->locale('id')->translatedFormat('j F Y') }} s.d. {{ $internship->end_date->locale('id')->translatedFormat('j F Y') }}</td></tr>
            <tr><th>Dosen pembimbing dan penguji</th><td>{{ $internship->lecturer?->name ?? '–' }}</td></tr>
            <tr><th>Company Supervisor</th><td>{{ $internship->supervisor?->name ?? $internship->supervisor_name ?? '–' }}</td></tr>
        </table>

        <h2>A. Nilai laporan akhir (dosen pembimbing)</h2>
        <table class="scores">
            <thead><tr><th>Aspek penilaian</th><th class="num">Bobot</th><th class="num">Nilai</th></tr></thead>
            <tbody>
                @foreach (\App\Models\ReportScore::ASPECTS as $key => [$label, $max])
                    <tr><td>{{ $label }}</td><td class="num">{{ $max }}</td><td class="num">{{ $report?->{$key} ?? '–' }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td>Jumlah</td><td class="num">100</td><td class="num">{{ $num($defense->lecturer_score) }}</td></tr></tfoot>
        </table>

        <h2>B. Nilai industri (Company Supervisor)</h2>
        <table class="scores">
            <thead><tr><th>Kategori</th><th class="num">Bobot</th><th class="num">Rata-rata</th></tr></thead>
            <tbody>
                @foreach ($industry['categories'] ?? [] as $cat)
                    <tr><td>{{ $cat['label'] }}</td><td class="num">{{ $num($cat['weight'], 0) }}%</td><td class="num">{{ $cat['average'] !== null ? $num($cat['average']) : '–' }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td>Nilai akhir industri</td><td class="num"></td><td class="num">{{ $num($defense->industry_score) }}</td></tr></tfoot>
        </table>

        <div class="result">
            <p>Nilai sidang = <b>A × 50%</b> + <b>B × 50%</b> = {{ $num($defense->lecturer_score) }} × 0,5 + {{ $num($defense->industry_score) }} × 0,5</p>
            <div class="result-score"><strong>{{ $num($defense->score) }}</strong><span>{{ $defense->letter_grade }}</span></div>
        </div>
        @if ($defense->note)<p class="note">Catatan: {{ $defense->note }}</p>@endif

        <div class="signs">
            <p class="place">{{ $campus['city'] }}, {{ $date->translatedFormat('j F Y') }}</p>
            <div class="sign">
                <div>Mahasiswa</div>
                <div class="space" aria-hidden="true"></div>
                <strong>{{ $student->name }}</strong>
                @if ($student->nim)<small>NIM {{ $student->nim }}</small>@endif
            </div>
            <div class="sign">
                <div>Dosen pembimbing dan penguji</div>
                <div class="space" aria-hidden="true"></div>
                <strong>{{ $examiner?->name ?? '……………………………' }}</strong>
            </div>
            <div class="sign">
                <div>Ketua Program Studi</div>
                <div class="space" aria-hidden="true"></div>
                <strong>{{ $defense->enteredBy?->name ?? '……………………………' }}</strong>
            </div>
        </div>

        <p class="foot">Dibuat oleh {{ config('app.name') }} pada {{ now()->locale('id')->translatedFormat('j F Y, H.i') }}. Nilai disahkan kaprodi pada {{ $defense->updated_at?->locale('id')->translatedFormat('j F Y') }}.</p>
    </article>
</div>
</body>
</html>
