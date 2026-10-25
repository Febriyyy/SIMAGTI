@php
    $student = $internship->student;
    $id = $student->academicIdentity();
    $issued = $document->processed_at ?? now();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Pengantar {{ $document->number }}</title>
    <style>
        @page { size: A4; margin: 22mm 22mm 20mm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #111; background: #eef1f0; margin: 0; }
        .sheet { background: #fff; max-width: 210mm; margin: 16px auto; padding: 22mm; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .kop { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 18px; }
        .kop h1 { font-size: 15pt; margin: 0; letter-spacing: .5px; text-transform: uppercase; }
        .kop h2 { font-size: 13pt; margin: 0; font-weight: normal; text-transform: uppercase; }
        .kop p { margin: 2px 0 0; font-size: 10pt; }
        table.meta td { padding: 0 8px 0 0; vertical-align: top; }
        table.data { margin: 8px 0 8px 24px; }
        table.data td { padding: 1px 10px 1px 0; vertical-align: top; }
        .sign { margin-top: 36px; width: 260px; margin-left: auto; }
        .sign .space { height: 70px; }
        .bar { max-width: 210mm; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; font-family: system-ui, sans-serif; }
        .bar button { padding: 8px 14px; border-radius: 8px; border: 1px solid #0e9b6b; background: #0e9b6b; color: #fff; cursor: pointer; font-size: 14px; }
        @media print { body { background: #fff; } .sheet { box-shadow: none; margin: 0; padding: 0; max-width: none; } .bar { display: none; } }
    </style>
</head>
<body>
<div class="bar"><button type="button" onclick="window.print()">Cetak / simpan PDF</button></div>
<main class="sheet">
    <header class="kop">
        <h1>{{ $campus['university'] }}</h1>
        <h2>{{ $campus['faculty'] }}</h2>
        @if ($campus['address'])<p>{{ $campus['address'] }}</p>@endif
    </header>

    <table class="meta">
        <tr><td>Nomor</td><td>:</td><td>{{ $document->number }}</td></tr>
        <tr><td>Lampiran</td><td>:</td><td>–</td></tr>
        <tr><td>Perihal</td><td>:</td><td><strong>Permohonan Kerja Praktik</strong></td></tr>
    </table>

    <p style="margin-top:16px">Kepada Yth.<br>
        Pimpinan {{ $internship->company?->name ?? 'Perusahaan' }}<br>
        @if ($internship->company?->city){{ $internship->company->city }}@endif
    </p>

    <p>Dengan hormat,</p>
    <p>Sehubungan dengan pelaksanaan Kerja Praktik (KP) bagi mahasiswa {{ $campus['faculty'] }} {{ $campus['university'] }}, kami mohon kesediaan Bapak/Ibu untuk menerima mahasiswa berikut melaksanakan KP di instansi yang Bapak/Ibu pimpin:</p>

    <table class="data">
        <tr><td>Nama</td><td>:</td><td>{{ $student->name }}</td></tr>
        <tr><td>NIM</td><td>:</td><td>{{ $student->nim }}</td></tr>
        <tr><td>Program studi</td><td>:</td><td>{{ $id['program'] ?? $campus['program'] }}</td></tr>
        <tr><td>Program</td><td>:</td><td>{{ $internship->programTypeLabel() }}</td></tr>
        @if ($internship->division || $internship->position)
            <tr><td>Penempatan</td><td>:</td><td>{{ collect([$internship->division?->name, $internship->position])->filter()->join(' · ') }}</td></tr>
        @endif
        <tr><td>Periode</td><td>:</td><td>{{ $internship->start_date->locale('id')->translatedFormat('j F Y') }} s.d. {{ $internship->end_date->locale('id')->translatedFormat('j F Y') }}</td></tr>
    </table>

    <p>Selama KP, kegiatan mahasiswa dicatat dan dipantau melalui aplikasi SIMAGTI. Pembimbing dari perusahaan cukup mengonfirmasi penempatan sekali dan memberi penilaian singkat setiap bulan.</p>
    <p>Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>

    <div class="sign">
        <p>{{ $campus['city'] }}, {{ $issued->locale('id')->translatedFormat('j F Y') }}<br>a.n. Dekan<br>Kepala Tata Usaha Fakultas,</p>
        <div class="space"></div>
        <p>(..............................................)</p>
    </div>
</main>
</body>
</html>
