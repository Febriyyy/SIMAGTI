<?php
    // Draf laporan KP (A4 tegak). Data diambil otomatis; kotak bergaris putus = bagian yang ditulis sendiri.
    $fmt = fn ($d) => \Illuminate\Support\Carbon::parse($d)->locale('id')->translatedFormat('j F Y');
    $short = fn ($d) => \Illuminate\Support\Carbon::parse($d)->locale('id')->translatedFormat('j M Y');
    $statusLabel = ['todo' => 'Belum dikerjakan', 'in_progress' => 'Dikerjakan', 'completed' => 'Selesai'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Draf Laporan KP {{ $student->name }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <style>
        /* Halaman cetak berdiri sendiri (A4 tegak). Warna mengikuti token merek di public/css/kp.css. */
        :root { --ink: #0e3b33; --primary: #2e7d16; --green: #6cc02a; --soft: #eaf5dc; --tint: #f2f9f7; --text: #142b29; --muted: #5d706d; --line: #d4dfdc; --page: #e6eeec; }
        @page { size: A4 portrait; margin: 20mm 20mm 22mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body { background: var(--page); color: var(--text); font-family: "Geist", system-ui, sans-serif; font-size: 15px; line-height: 1.65; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; padding: 12px 16px; background: rgba(230, 238, 236, 0.92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
        .toolbar p { flex: 1 1 260px; margin: 0; color: var(--muted); font-size: 14px; }
        .toolbar a, .toolbar button { min-height: 40px; padding: 8px 16px; border: 1px solid var(--line); border-radius: 999px; background: #fff; color: var(--primary); font: 600 14px/1.4 inherit; text-decoration: none; cursor: pointer; }
        .toolbar .primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        .toolbar a:focus-visible, .toolbar button:focus-visible { outline: 3px solid var(--green); outline-offset: 2px; }
        .toast { position: fixed; left: 50%; bottom: 24px; z-index: 3; transform: translate(-50%, 20px); padding: 10px 18px; border-radius: 999px; background: var(--ink); color: #fff; font-size: 14px; font-weight: 600; opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s; }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }

        .doc { width: 210mm; max-width: calc(100% - 32px); margin: 24px auto 48px; padding: 22mm 20mm; background: #fff; box-shadow: 0 12px 40px rgba(14, 59, 51, .14); }
        .cover { display: flex; flex-direction: column; align-items: center; min-height: 230mm; padding-top: 12mm; text-align: center; page-break-after: always; }
        .cover .kicker { margin: 0; color: var(--primary); font-size: 13px; font-weight: 700; letter-spacing: .3em; text-transform: uppercase; }
        .cover h1 { margin: 6mm 0 0; color: var(--ink); font-size: 30px; line-height: 1.2; }
        .cover .place { margin: 4mm 0 0; color: var(--muted); font-size: 16px; }
        .cover img { width: 34mm; margin: 22mm 0; }
        .cover .who { font-size: 16px; }
        .cover .who strong { display: block; font-size: 20px; }
        .cover .campus { margin-top: auto; color: var(--ink); font-weight: 700; font-size: 16px; line-height: 1.5; text-transform: uppercase; }
        .cover .note { margin-top: 10mm; padding: 3mm 5mm; border-radius: 3mm; background: var(--tint); color: var(--muted); font-size: 12.5px; }

        h2 { margin: 12mm 0 4mm; padding-bottom: 2mm; border-bottom: .5mm solid var(--green); color: var(--ink); font-size: 20px; page-break-after: avoid; }
        h2:first-of-type { margin-top: 0; }
        h3 { margin: 7mm 0 2mm; color: var(--text); font-size: 16px; page-break-after: avoid; }
        p { margin: 0 0 3mm; }
        .fill { margin: 2mm 0 4mm; padding: 4mm 5mm; border: .4mm dashed #9fcfbf; border-radius: 2.5mm; background: var(--tint); color: var(--muted); font-style: italic; }
        table { width: 100%; margin: 2mm 0 5mm; border-collapse: collapse; font-size: 13.5px; page-break-inside: auto; }
        th, td { padding: 2mm 3mm; border: .3mm solid var(--line); text-align: left; vertical-align: top; }
        th { background: var(--soft); color: var(--ink); font-weight: 700; }
        tr { page-break-inside: avoid; }
        .muted { color: var(--muted); }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        ul.plain { margin: 0 0 4mm; padding-left: 5mm; }
        .week { page-break-inside: avoid; }
        .week-head { display: flex; justify-content: space-between; gap: 4mm; color: var(--ink); font-weight: 700; }
        .week-head span { color: var(--muted); font-weight: 500; font-size: 13px; }
        .day { display: grid; grid-template-columns: 30mm 1fr; gap: 3mm; padding: 1.5mm 0; border-bottom: .2mm solid var(--line); font-size: 13.5px; }
        .day b { font-weight: 600; }
        .tag { display: inline-block; margin-left: 2mm; padding: 0 2mm; border-radius: 99px; background: var(--soft); color: var(--ink); font-size: 11.5px; font-weight: 600; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 3mm; margin: 2mm 0 5mm; }
        .stats div { padding: 3mm 4mm; border-radius: 2.5mm; background: var(--tint); }
        .stats b { display: block; color: var(--ink); font-size: 22px; line-height: 1.1; }
        .stats span { color: var(--muted); font-size: 12.5px; }
        @media (max-width: 700px) {
            .doc { padding: 20px 18px; }
            .cover { min-height: 0; padding: 24px 0; }
            .cover img { margin: 32px 0; }
            .day { grid-template-columns: 1fr; gap: 0; }
            .stats { grid-template-columns: repeat(2, 1fr); }
        }
        @media print {
            body { background: #fff; font-size: 11pt; }
            .toolbar, .toast { display: none; }
            .doc { width: auto; max-width: none; margin: 0; padding: 0; box-shadow: none; }
            .cover { min-height: 250mm; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <p>Draf otomatis dari data KP Anda. Lengkapi bagian bergaris putus, lalu salin ke Word atau simpan sebagai PDF.</p>
    <a href="{{ route('timesheet.index') }}">Kembali</a>
    <button type="button" data-copy-doc>Salin teks</button>
    <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<article class="doc" id="draf-laporan" aria-label="Draf laporan Kerja Praktik">
    <section class="cover">
        <p class="kicker">Laporan Kerja Praktik</p>
        <h1>{{ $internship->position ?? 'Kerja Praktik' }}<br>di {{ $internship->placeName() }}</h1>
        <p class="place">{{ $fmt($internship->start_date) }} – {{ $fmt($internship->end_date) }}</p>
        <img src="{{ asset('images/logo-simagti-mark.svg') }}" alt="">
        <div class="who">
            Disusun oleh
            <strong>{{ $student->name }}</strong>
            @if ($student->nim)NIM {{ $student->nim }}@endif
        </div>
        <div class="campus">{{ $campus['program'] }}<br>{{ $campus['faculty'] }}<br>{{ $campus['university'] }}<br>{{ $internship->end_date->format('Y') }}</div>
        <p class="note">Draf disusun otomatis oleh SIMAGTI pada {{ $fmt(now()) }}. Sesuaikan dengan format laporan dari program studi.</p>
    </section>

    <h2>Bab I · Pendahuluan</h2>
    <h3>1.1 Latar belakang</h3>
    <div class="fill">[Tulis latar belakang: alasan memilih tempat KP dan kaitannya dengan bidang studi.]</div>
    <h3>1.2 Tujuan Kerja Praktik</h3>
    @if ($goals->isNotEmpty())
        <p>Tujuan yang ditetapkan selama KP:</p>
        <ul class="plain">
            @foreach ($goals as $goal)
                <li>{{ $goal->title }}@if ($goal->achieved_at) <span class="tag">tercapai</span>@endif</li>
            @endforeach
        </ul>
    @else
        <div class="fill">[Tulis tujuan Kerja Praktik.]</div>
    @endif
    <h3>1.3 Waktu dan tempat pelaksanaan</h3>
    <table>
        <tr><th style="width: 34%">Perusahaan</th><td>{{ $internship->placeName() }}@if ($internship->company?->city), {{ $internship->company->city }}@endif</td></tr>
        @if ($internship->division)<tr><th>Divisi</th><td>{{ $internship->division->name }}</td></tr>@endif
        <tr><th>Posisi</th><td>{{ $internship->position ?? '–' }}</td></tr>
        <tr><th>Periode</th><td>{{ $fmt($internship->start_date) }} – {{ $fmt($internship->end_date) }} ({{ $internship->totalWeeks() }} minggu)</td></tr>
        <tr><th>Company Supervisor</th><td>{{ $internship->supervisor?->name ?? '–' }}@if ($internship->supervisor_title), {{ $internship->supervisor_title }}@endif</td></tr>
        <tr><th>Dosen pembimbing</th><td>{{ $internship->lecturer?->name ?? '–' }}</td></tr>
    </table>

    <h2>Bab II · Profil Tempat Kerja Praktik</h2>
    <div class="fill">[Tulis profil singkat {{ $internship->placeName() }}: bidang usaha, struktur organisasi, dan peran divisi tempat Anda bekerja.]</div>

    <h2>Bab III · Rencana dan Pelaksanaan Kegiatan</h2>
    <div class="stats">
        <div><b>{{ $stats['plans'] }}</b><span>rencana kegiatan</span></div>
        <div><b>{{ $stats['done'] }}/{{ $stats['tasks'] }}</b><span>task selesai</span></div>
        <div><b>{{ $stats['days'] }}</b><span>hari tercatat</span></div>
        <div><b>{{ $sessions->count() }}</b><span>bimbingan</span></div>
    </div>
    @forelse ($plans as $i => $plan)
        <h3>3.{{ $i + 1 }} {{ $plan->title }}</h3>
        <p class="muted">{{ $short($plan->start_date) }} – {{ $short($plan->end_date) }}@if ($plan->goal) · mendukung goal "{{ $plan->goal->title }}"@endif</p>
        @if ($plan->tasks->isNotEmpty())
            <table>
                <thead><tr><th style="width: 16%">Kode</th><th>Task</th><th style="width: 22%">Status</th></tr></thead>
                <tbody>
                    @foreach ($plan->tasks as $task)
                        <tr><td>{{ $task->key() }}</td><td>{{ $task->title }}</td><td>{{ $statusLabel[$task->status] ?? $task->status }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <div class="fill">[Jelaskan apa yang dikerjakan pada rencana ini, kendala, dan cara menyelesaikannya.]</div>
    @empty
        <div class="fill">[Belum ada rencana kegiatan. Susun rencana di Timesheet agar bagian ini terisi otomatis.]</div>
    @endforelse

    <h2>Bab IV · Uraian Kegiatan Mingguan</h2>
    @forelse ($weeks as $week)
        <div class="week">
            <h3 class="week-head">Minggu ke-{{ $week['number'] }} <span>{{ $short($week['from']) }} – {{ $short($week['to']) }}</span></h3>
            @foreach ($week['entries'] as $entry)
                <div class="day">
                    <b>{{ \Illuminate\Support\Carbon::parse($entry->work_date)->locale('id')->translatedFormat('D, j M') }}</b>
                    <span>{{ $entry->description }}@if ($entry->task)<span class="tag">{{ $entry->task->key() }}</span>@endif</span>
                </div>
            @endforeach
        </div>
    @empty
        <div class="fill">[Belum ada catatan kegiatan. Isi catatan harian di Timesheet agar bagian ini terisi otomatis.]</div>
    @endforelse

    <h2>Bab V · Bimbingan</h2>
    @if ($sessions->isNotEmpty())
        <table>
            <thead><tr><th style="width: 26%">Tanggal</th><th>Topik</th><th style="width: 18%">Cara</th></tr></thead>
            <tbody>
                @foreach ($sessions as $s)
                    <tr><td>{{ $short($s->held_on) }}</td><td>{{ $s->topic }}</td><td>{{ $s->modeLabel() }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">Belum ada bimbingan yang dikonfirmasi dosen pembimbing.</p>
    @endif

    <h2>Bab VI · Penutup</h2>
    <h3>6.1 Kesimpulan</h3>
    <div class="fill">[Tulis kesimpulan: hasil utama KP dan kompetensi yang diperoleh.]</div>
    <h3>6.2 Saran</h3>
    <div class="fill">[Tulis saran untuk tempat KP dan program studi.]</div>
</article>

<div class="toast" role="status" aria-live="polite" data-toast></div>
<script>
    (function () {
        var btn = document.querySelector('[data-copy-doc]');
        var toast = document.querySelector('[data-toast]');
        function show(msg) { toast.textContent = msg; toast.classList.add('show'); setTimeout(function () { toast.classList.remove('show'); }, 2200); }
        btn.addEventListener('click', function () {
            var text = document.getElementById('draf-laporan').innerText;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function () { show('Teks laporan disalin. Tempel di Word.'); }, fallback);
            } else { fallback(); }
            function fallback() {
                var area = document.createElement('textarea');
                area.value = text; area.setAttribute('readonly', ''); area.style.position = 'fixed'; area.style.opacity = '0';
                document.body.appendChild(area); area.select();
                try { document.execCommand('copy'); show('Teks laporan disalin. Tempel di Word.'); } catch (e) { show('Gagal menyalin. Pilih teks lalu tekan Ctrl+C.'); }
                area.remove();
            }
        });
    })();
</script>
</body>
</html>
