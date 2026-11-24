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
    $rows = array_filter([
        'Posisi' => $offer->offered_position,
        'Divisi' => $offer->unit,
        'Lokasi penempatan' => $offer->work_location,
        'Cara kerja' => $offer->workModeLabel(),
        'Hubungan kerja' => $offer->employmentLabel(),
        'Perkiraan mulai' => $offer->start_date?->locale('id')->translatedFormat('j F Y'),
        'Kompensasi' => $offer->compensation,
    ]);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Penawaran Kerja {{ $offer->letterNumber() }}</title>
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
        .sheet { position: relative; width: 210mm; min-height: 297mm; margin: 16px auto 32px; padding: 18mm 20mm 26mm; background: #fff; box-shadow: 0 10px 40px rgba(15, 29, 23, .12); overflow: hidden; }
        .sheet::before { content: ""; position: absolute; left: 0; top: 0; right: 0; height: 6px; background: linear-gradient(90deg, var(--forest), var(--green) 60%, var(--lime)); }

        .kop { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
        .kop-brand { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .kop-logo { width: 52px; height: 52px; flex: none; display: grid; place-items: center; border-radius: 12px; background: var(--cream); color: var(--forest); font-weight: 700; font-size: 15pt; overflow: hidden; }
        .kop-logo img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
        .kop-name { font-family: Fraunces, Georgia, serif; font-size: 16pt; font-weight: 600; line-height: 1.2; color: var(--forest); }
        .kop-ind { font-size: 9pt; color: var(--muted); }
        .kop-contact { text-align: right; font-size: 8.5pt; line-height: 1.5; color: var(--muted); }

        .title { margin: 26px 0 4px; font-family: Fraunces, Georgia, serif; font-size: 20pt; font-weight: 600; letter-spacing: -.01em; color: var(--forest); }
        .meta { display: flex; justify-content: space-between; gap: 16px; font-size: 9.5pt; color: var(--muted); }
        .meta b { color: var(--ink); font-weight: 600; }

        .to { margin: 22px 0 18px; }
        .to p { margin: 0; }
        .to .name { font-weight: 700; font-size: 11pt; }
        p { margin: 0 0 10px; }

        .terms { width: 100%; margin: 14px 0 18px; border-collapse: separate; border-spacing: 0; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
        .terms th, .terms td { padding: 9px 14px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--line); }
        .terms tr:last-child th, .terms tr:last-child td { border-bottom: 0; }
        .terms th { width: 38%; background: #f6f9f7; font-weight: 500; color: var(--muted); }
        .terms td { font-weight: 600; }

        h2 { margin: 18px 0 8px; font-size: 10.5pt; font-weight: 700; color: var(--forest); }
        ul { margin: 0 0 14px; padding-left: 18px; }
        li { margin-bottom: 4px; }
        li::marker { color: var(--green); }

        .deadline { display: flex; align-items: center; gap: 10px; margin: 16px 0; padding: 12px 14px; border-radius: 12px; background: var(--cream); font-size: 10pt; }
        .deadline b { color: var(--forest); }

        .sign { display: flex; justify-content: flex-end; margin-top: 28px; }
        .sign-box { width: 70mm; text-align: left; }
        .sign-stamp { display: flex; align-items: center; gap: 10px; margin: 12px 0 10px; padding: 10px 12px; border: 1px dashed #9fc6b0; border-radius: 10px; color: var(--green); font-size: 8.5pt; line-height: 1.4; }
        .sign-stamp svg { flex: none; }
        .sign-name { font-weight: 700; }
        .sign-title { font-size: 9pt; color: var(--muted); }

        .foot { position: absolute; left: 20mm; right: 20mm; bottom: 12mm; display: flex; justify-content: space-between; gap: 16px; padding-top: 8px; border-top: 1px solid var(--line); font-size: 7.5pt; color: var(--muted); }

        @media screen and (max-width: 860px) {
            .sheet { width: auto; min-height: 0; margin: 12px; padding: 24px 20px 64px; }
            .kop { flex-direction: column; align-items: flex-start; }
            .kop-contact { text-align: left; }
            .bar { margin: 12px; }
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
<main class="sheet">
    <header class="kop">
        <div class="kop-brand">
            <span class="kop-logo">
                @if ($company?->logo_file_id)
                    <img src="{{ route('logo.perusahaan', $company) }}?v={{ $company->logo_file_id }}" alt="Logo {{ $company->name }}">
                @else
                    {{ collect(preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|Yayasan)\.?\s+/i', '', (string) $company?->name))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}
                @endif
            </span>
            <div class="min0">
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

    <h1 class="title">Surat Penawaran Kerja</h1>
    <div class="meta">
        <span>Nomor <b>{{ $offer->letterNumber() }}</b></span>
        <span>{{ $city }}, {{ $date->locale('id')->translatedFormat('j F Y') }}</span>
    </div>

    <div class="to">
        <p>Kepada Yth.</p>
        <p class="name">{{ $student->name }}</p>
        <p>NIM {{ $student->nim ?? '–' }}{{ $academic['program'] ? ' · '.$academic['program'] : '' }}</p>
        <p>{{ $academic['university'] }}</p>
    </div>

    <p>Dengan hormat,</p>
    <p>
        Berdasarkan kinerja Saudara/i selama kerja praktik di {{ $company?->name }}
        ({{ $internship->periodLabel() }}){{ $internship->division ? ', '.'Divisi '.$internship->division->name : '' }},
        dengan senang hati kami menawarkan posisi berikut kepada Saudara/i.
    </p>

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

    @if ($offer->respond_by)
        <div class="deadline">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0a7c4e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            <span>Mohon sampaikan jawaban Saudara/i paling lambat <b>{{ $offer->respond_by->locale('id')->translatedFormat('l, j F Y') }}</b> melalui SIMAGTI.</span>
        </div>
    @endif

    <p>
        Ketentuan lebih rinci akan disampaikan dalam perjanjian kerja setelah Saudara/i menerima penawaran ini.
        @if ($offer->hr_contact_email) Pertanyaan dapat diajukan ke {{ $offer->hr_contact_email }}. @endif
    </p>
    <p>Kami berharap dapat bekerja sama dengan Saudara/i.</p>

    <div class="sign">
        <div class="sign-box">
            <p style="margin: 0">Hormat kami,</p>
            <p style="margin: 0">{{ $company?->name }}</p>
            <div class="sign-stamp">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8.2-7 10-4-1.8-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
                <span>Ditandatangani secara elektronik<br>{{ $date->locale('id')->translatedFormat('j F Y, H:i') }}</span>
            </div>
            <div class="sign-name">{{ $signer }}</div>
            <div class="sign-title">{{ $signerTitle }}</div>
        </div>
    </div>

    <footer class="foot">
        <span>Diterbitkan melalui SIMAGTI · Sistem Monitoring Magang Terintegrasi</span>
        <span>{{ $offer->letterNumber() }}</span>
    </footer>
</main>
</body>
</html>
