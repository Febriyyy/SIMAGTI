@php
    $company = $internship->company;
    $student = $internship->student;
    $academic = $student->academicIdentity();
    $date = $offer->offered_at ?? $offer->created_at ?? now();
    $signer = $offer->hr_contact_name ?: ($internship->supervisor?->name ?? 'Bagian SDM');
    $signerTitle = $offer->hr_contact_name && $internship->supervisor && $offer->hr_contact_name === $internship->supervisor->name
        ? ($internship->supervisor->staffProfile?->job_title ?: 'Company Supervisor')
        : 'Human Resources';
    $points = $offer->jobPoints();
    $city = $company?->city ?: \App\Support\Campus::city();
    $initials = collect(preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|Yayasan)\.?\s+/i', '', (string) $company?->name))))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $rows = array_filter([
        'Posisi' => $offer->offered_position,
        'Divisi' => $offer->unit,
        'Lokasi penempatan' => $offer->work_location,
        'Cara kerja' => $offer->workModeLabel(),
        'Hubungan kerja' => $offer->employmentLabel(),
        'Perkiraan mulai bekerja' => $offer->start_date?->locale('id')->translatedFormat('j F Y'),
        'Kompensasi' => $offer->compensation,
    ]);
    $deadline = $offer->respond_by?->locale('id')->translatedFormat('l, j F Y');
    $number = $offer->letterNumber();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Penawaran Kerja {{ $number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        :root { --ink: #0f1d17; --muted: #5b6a63; --line: #dfe6e2; --green: #0a7c4e; --forest: #234635; --cream: #f2f9dd; --lime: #c5ef78; }
        body { margin: 0; background: #e9eeeb; color: var(--ink); font-family: "Plus Jakarta Sans", system-ui, sans-serif; font-size: 10.5pt; line-height: 1.6; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bar { max-width: 210mm; margin: 16px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .bar button { height: 40px; padding: 0 18px; border: 0; border-radius: 10px; background: var(--forest); color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 16px auto 32px; padding: 18mm 20mm 26mm; background: #fff; box-shadow: 0 10px 40px rgba(15, 29, 23, .12); overflow: hidden; page-break-after: always; }
        .sheet:last-of-type { page-break-after: auto; }
        .sheet::before { content: ""; position: absolute; left: 0; top: 0; right: 0; height: 6px; background: linear-gradient(90deg, var(--forest), var(--green) 60%, var(--lime)); }
        .sheet::after { content: "{{ $initials }}"; position: absolute; right: -10mm; bottom: 30mm; font-family: Fraunces, Georgia, serif; font-size: 150pt; font-weight: 600; line-height: 1; color: rgba(10, 124, 78, .035); pointer-events: none; }

        .kop { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
        .kop-brand { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .kop-logo { width: 54px; height: 54px; flex: none; display: grid; place-items: center; border-radius: 12px; background: var(--cream); color: var(--forest); font-weight: 700; font-size: 15pt; overflow: hidden; }
        .kop-logo img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
        .kop-name { font-family: Fraunces, Georgia, serif; font-size: 16pt; font-weight: 600; line-height: 1.2; color: var(--forest); }
        .kop-ind { font-size: 9pt; color: var(--muted); }
        .kop-contact { text-align: right; font-size: 8.5pt; line-height: 1.5; color: var(--muted); }

        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-top: 20px; }
        .memo { border-collapse: collapse; font-size: 10pt; }
        .memo td { padding: 1px 0; vertical-align: top; }
        .memo td:first-child { width: 22mm; color: var(--muted); }
        .memo td:nth-child(2) { width: 5mm; color: var(--muted); }
        .memo b { font-weight: 600; }
        .place { font-size: 10pt; white-space: nowrap; }
        .conf { display: inline-block; margin-top: 6px; padding: 2px 10px; border: 1px solid var(--green); border-radius: 999px; color: var(--green); font-size: 7.5pt; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }

        .title { margin: 22px 0 2px; font-family: Fraunces, Georgia, serif; font-size: 20pt; font-weight: 600; letter-spacing: -.01em; color: var(--forest); }
        .to { margin: 14px 0 16px; }
        .to p { margin: 0; }
        .to .name { font-weight: 700; font-size: 11pt; }
        p { margin: 0 0 10px; text-align: justify; }

        h2 { margin: 18px 0 8px; font-size: 10.5pt; font-weight: 700; color: var(--forest); }
        .terms { width: 100%; margin: 0 0 6px; border-collapse: separate; border-spacing: 0; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
        .terms th, .terms td { padding: 8px 14px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--line); }
        .terms tr:last-child th, .terms tr:last-child td { border-bottom: 0; }
        .terms th { width: 40%; background: #f6f9f7; font-weight: 500; color: var(--muted); }
        .terms td { font-weight: 600; }
        ul, ol { margin: 0 0 10px; padding-left: 18px; }
        li { margin-bottom: 3px; }
        li::marker { color: var(--green); font-weight: 600; }

        .deadline { display: flex; align-items: center; gap: 10px; margin: 14px 0; padding: 11px 14px; border-radius: 12px; background: var(--cream); font-size: 10pt; }
        .deadline b { color: var(--forest); }

        .sign { display: flex; justify-content: flex-end; margin-top: 22px; }
        .sign-box { position: relative; width: 74mm; }
        .sign-box p { margin: 0; text-align: left; }
        .sign-row { display: flex; align-items: center; gap: 12px; margin: 10px 0; }
        .seal { width: 64px; height: 64px; flex: none; }
        .sign-stamp { color: var(--green); font-size: 8pt; line-height: 1.45; }
        .sign-name { font-weight: 700; border-top: 1px solid var(--ink); padding-top: 4px; }
        .sign-title { font-size: 9pt; color: var(--muted); }

        .accept-title { margin: 0 0 4px; font-family: Fraunces, Georgia, serif; font-size: 16pt; font-weight: 600; color: var(--forest); }
        .accept-box { margin-top: 16px; padding: 16px 18px; border: 1px solid var(--line); border-radius: 12px; }
        .choice { display: flex; gap: 28px; margin: 12px 0 4px; }
        .choice span { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; }
        .choice i { width: 14px; height: 14px; border: 1.5px solid var(--ink); border-radius: 3px; }
        .lines { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-top: 26px; }
        .line { padding-top: 46px; border-bottom: 1px solid var(--ink); }
        .line-label { margin-top: 4px; font-size: 9pt; color: var(--muted); }

        .foot { position: absolute; left: 20mm; right: 20mm; bottom: 12mm; display: flex; justify-content: space-between; gap: 16px; padding-top: 8px; border-top: 1px solid var(--line); font-size: 7.5pt; color: var(--muted); }

        @media screen and (max-width: 860px) {
            .sheet { width: auto; min-height: 0; margin: 12px; padding: 24px 20px 64px; }
            .kop, .head { flex-direction: column; align-items: flex-start; }
            .kop-contact { text-align: left; }
            .bar { margin: 12px; }
            .lines { grid-template-columns: 1fr; }
            .foot { left: 20px; right: 20px; bottom: 16px; }
        }
        @media print {
            body { background: #fff; }
            .bar { display: none; }
            .sheet { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="bar"><button type="button" onclick="window.print()">Cetak / simpan PDF</button></div>

{{-- Kop surat yang sama untuk kedua halaman --}}
@php
    ob_start();
@endphp
<header class="kop">
    <div class="kop-brand">
        <span class="kop-logo">
            @if ($company?->logo_file_id)
                <img src="{{ route('logo.perusahaan', $company) }}?v={{ $company->logo_file_id }}" alt="Logo {{ $company->name }}">
            @else
                {{ $initials }}
            @endif
        </span>
        <div>
            <div class="kop-name">{{ $company?->name }}</div>
            @if ($company?->industry)<div class="kop-ind">{{ $company->industry }}</div>@endif
        </div>
    </div>
    <div class="kop-contact">
        @if ($company?->address)<div>{{ $company->address }}</div>@endif
        @if ($company?->city)<div>{{ $company->city }}</div>@endif
        @if ($company?->phone)<div>{{ $company->phone }}</div>@endif
        @if ($company?->email)<div>{{ $company->email }}</div>@endif
    </div>
</header>
@php
    $kop = ob_get_clean();
@endphp

{{-- Halaman 1: surat penawaran --}}
<main class="sheet">
    {!! $kop !!}

    <div class="head">
        <table class="memo">
            <tr><td>Nomor</td><td>:</td><td><b>{{ $number }}</b></td></tr>
            <tr><td>Lampiran</td><td>:</td><td>1 (satu) lembar pernyataan</td></tr>
            <tr><td>Perihal</td><td>:</td><td><b>Penawaran Kerja</b></td></tr>
        </table>
        <div style="text-align: right">
            <div class="place">{{ $city }}, {{ $date->locale('id')->translatedFormat('j F Y') }}</div>
            <span class="conf">Rahasia</span>
        </div>
    </div>

    <div class="to">
        <p>Kepada Yth.</p>
        <p class="name">Sdr./Sdri. {{ $student->name }}</p>
        <p>NIM {{ $student->nim ?? '–' }}{{ $academic['program'] ? ' · '.$academic['program'] : '' }}</p>
        <p>{{ $academic['university'] }}</p>
    </div>

    <p>Dengan hormat,</p>
    <p>
        Kami menyampaikan apresiasi atas dedikasi dan kinerja Saudara/i selama menjalani kerja praktik di {{ $company?->name }}
        pada periode {{ $internship->periodLabel() }}{{ $internship->division ? ', di Divisi '.$internship->division->name : '' }}.
        Berdasarkan hasil evaluasi tersebut, manajemen {{ $company?->name }} dengan ini secara resmi menawarkan kepada Saudara/i
        kesempatan untuk bergabung sebagai bagian dari tim kami, dengan rincian sebagai berikut.
    </p>

    <h2>Rincian penawaran</h2>
    <table class="terms">
        @foreach ($rows as $label => $value)
            <tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    @if ($points)
        <h2>Tanggung jawab utama</h2>
        <ul>
            @foreach ($points as $point)<li>{{ $point }}</li>@endforeach
        </ul>
    @endif

    <h2>Ketentuan penawaran</h2>
    <ol>
        @if ($deadline)
            <li>Penawaran ini berlaku sampai <b>{{ $deadline }}</b>. Apabila sampai tanggal tersebut belum ada jawaban, penawaran dianggap tidak diambil.</li>
        @endif
        <li>Perjanjian kerja resmi beserta ketentuan rinci akan diberikan setelah Saudara/i menyatakan menerima penawaran ini.</li>
        <li>Penawaran ini bersifat pribadi dan rahasia, hanya ditujukan kepada Saudara/i.</li>
    </ol>

    @if ($deadline)
        <div class="deadline">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0a7c4e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span>Jawaban disampaikan melalui SIMAGTI paling lambat <b>{{ $deadline }}</b>, disertai lembar pernyataan terlampir.</span>
        </div>
    @endif

    <p>
        Kami percaya kemampuan dan semangat yang telah Saudara/i tunjukkan akan memberi kontribusi berarti bagi perusahaan.
        @if ($offer->hr_contact_email) Pertanyaan mengenai penawaran ini dapat disampaikan kepada {{ $offer->hr_contact_name ?: 'bagian SDM' }} melalui {{ $offer->hr_contact_email }}. @endif
        Kami menantikan kabar baik dari Saudara/i.
    </p>

    <div class="sign">
        <div class="sign-box">
            <p>Hormat kami,</p>
            <p><b>{{ $company?->name }}</b></p>
            <div class="sign-row">
                <svg class="seal" viewBox="0 0 120 120" aria-hidden="true">
                    <defs><path id="seal-arc" d="M60 60m-44 0a44 44 0 1 1 88 0a44 44 0 1 1-88 0"/></defs>
                    <circle cx="60" cy="60" r="57" fill="none" stroke="#0a7c4e" stroke-width="2"/>
                    <circle cx="60" cy="60" r="34" fill="none" stroke="#0a7c4e" stroke-width="1"/>
                    <text font-size="9.5" font-weight="700" letter-spacing="1.5" fill="#0a7c4e" font-family="Plus Jakarta Sans, sans-serif"><textPath href="#seal-arc">{{ mb_strtoupper(\Illuminate\Support\Str::limit((string) $company?->name, 34, '')) }} · SEGEL ELEKTRONIK ·</textPath></text>
                    <text x="60" y="68" text-anchor="middle" font-size="24" font-weight="700" fill="#234635" font-family="Fraunces, Georgia, serif">{{ $initials }}</text>
                </svg>
                <div class="sign-stamp">Ditandatangani secara elektronik<br>{{ $date->locale('id')->translatedFormat('j F Y, H:i') }}</div>
            </div>
            <div class="sign-name">{{ $signer }}</div>
            <div class="sign-title">{{ $signerTitle }}</div>
        </div>
    </div>

    <footer class="foot">
        <span>Diterbitkan melalui SIMAGTI · Sistem Monitoring Magang Terintegrasi</span>
        <span>{{ $number }} · Halaman 1 dari 2</span>
    </footer>
</main>

{{-- Halaman 2: lembar pernyataan calon karyawan --}}
<main class="sheet">
    {!! $kop !!}

    <h1 class="accept-title" style="margin-top: 22px">Lembar Pernyataan</h1>
    <p style="color: var(--muted)">Lampiran surat nomor {{ $number }} perihal Penawaran Kerja</p>

    <div class="accept-box">
        <p>Yang bertanda tangan di bawah ini:</p>
        <table class="memo" style="margin-bottom: 12px">
            <tr><td style="width: 34mm">Nama</td><td>:</td><td><b>{{ $student->name }}</b></td></tr>
            <tr><td style="width: 34mm">NIM</td><td>:</td><td>{{ $student->nim ?? '–' }}</td></tr>
            <tr><td style="width: 34mm">Program studi</td><td>:</td><td>{{ $academic['program'] ?? '–' }}</td></tr>
            <tr><td style="width: 34mm">Posisi ditawarkan</td><td>:</td><td><b>{{ $offer->offered_position }}</b>{{ $offer->unit ? ', '.$offer->unit : '' }}</td></tr>
        </table>
        <p>dengan ini menyatakan bahwa saya telah membaca dan memahami penawaran kerja dari {{ $company?->name }}, dan memutuskan untuk:</p>
        <div class="choice">
            <span><i></i> Menerima penawaran</span>
            <span><i></i> Menolak penawaran</span>
        </div>
        <p style="margin-top: 10px">Pernyataan ini saya buat dengan sadar dan tanpa paksaan dari pihak mana pun.</p>

        <div class="lines">
            <div>
                <div class="line"></div>
                <div class="line-label">Tempat dan tanggal</div>
            </div>
            <div>
                <div class="line"></div>
                <div class="line-label">{{ $student->name }}</div>
            </div>
        </div>
    </div>

    <p style="margin-top: 18px; font-size: 9pt; color: var(--muted)">
        Lembar ini dapat diunggah atau diserahkan kepada {{ $offer->hr_contact_name ?: 'bagian SDM' }} sebagai kelengkapan jawaban atas penawaran kerja.
        Jawaban resmi tetap dicatat melalui SIMAGTI.
    </p>

    <footer class="foot">
        <span>Diterbitkan melalui SIMAGTI · Sistem Monitoring Magang Terintegrasi</span>
        <span>{{ $number }} · Halaman 2 dari 2</span>
    </footer>
</main>
</body>
</html>
