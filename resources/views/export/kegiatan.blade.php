@php
    $student = $internship->student;
    $id = $student->academicIdentity();
    $byProject = $report['tasks']->groupBy(fn ($t) => $t->project?->name ?? 'Tanpa proyek');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Kegiatan KP · {{ $student->name }}</title>
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Times New Roman', Times, serif; font-size: 11.5pt; color: #000; background: #f2f2f2; }
        .sheet { width: 210mm; min-height: 297mm; margin: 12px auto; padding: 18mm 16mm; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.15); }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: center; padding: 10px; background: #03423e; font-family: system-ui, sans-serif; }
        .toolbar button { padding: 8px 18px; border: 0; border-radius: 999px; font-weight: 600; cursor: pointer; }
        .toolbar .primary { background: #02a86f; color: #fff; }
        .kop { text-align: center; font-weight: bold; line-height: 1.35; text-transform: uppercase; }
        .kop small { display: block; font-weight: normal; text-transform: none; }
        h2 { margin: 22px 0 8px; font-size: 12pt; text-transform: uppercase; }
        .ident { width: 100%; margin: 14px 0 10px; border-collapse: collapse; }
        .ident td { padding: 2px 4px; vertical-align: top; }
        .ident td:first-child { width: 34%; }
        table.grid { width: 100%; border-collapse: collapse; }
        .grid th, .grid td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        .grid th { background: #eee; text-align: center; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .sign { width: 70mm; margin: 26px 0 0 auto; text-align: center; }
        .sign .space { height: 22mm; }
        .muted { color: #555; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; padding: 0; width: auto; min-height: 0; box-shadow: none; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <div class="sheet">
        <div class="kop">
            Form Absensi dan Aktivitas Pelaksanaan {{ $internship->isSeminar() ? 'Kegiatan' : 'Kerja Praktik' }}<br>
            Pada: {{ $internship->placeName() }}<br>
            {{ $id['program'] ? 'Program Studi '.$id['program'] : '' }}<br>
            {{ $id['faculty'] }}<br>
            {{ $id['university'] }}
        </div>

        <table class="ident">
            <tr><td>Nama</td><td>: {{ $student->name }}</td></tr>
            <tr><td>NIM</td><td>: {{ $student->nim }}</td></tr>
            <tr><td>{{ $internship->isSeminar() ? 'Kegiatan' : 'Unit kerja / posisi' }}</td><td>: {{ $internship->isSeminar() ? $internship->programTypeLabel() : trim(($internship->division?->name ?? '').' · '.($internship->position ?? ''), ' ·') }}</td></tr>
            <tr><td>Periode laporan</td><td>: {{ $report['range'] }}</td></tr>
        </table>

        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 7%">No.</th>
                    <th style="width: 20%">Hari/Tanggal</th>
                    <th>Aktivitas Pekerjaan</th>
                    <th style="width: 9%">Jam</th>
                    <th style="width: 17%">Paraf {{ $internship->isSeminar() ? 'Dosen' : 'Pembimbing Industri' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['entries'] as $i => $entry)
                    <tr>
                        <td class="center">{{ $i + 1 }}.</td>
                        <td>{{ $entry->work_date->copy()->locale('id')->translatedFormat('l, j F Y') }}</td>
                        <td>{{ $entry->description }}@if ($entry->task) <span class="muted">({{ $entry->task->key() }})</span>@endif</td>
                        <td class="num">{{ \App\Support\Num::short((float) $entry->hours) }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="center muted">Tidak ada catatan pada periode ini.</td></tr>
                @endforelse
                <tr>
                    <td colspan="3" class="num"><strong>Total jam</strong></td>
                    <td class="num"><strong>{{ \App\Support\Num::short($report['hours']) }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="sign">
            {{ \App\Support\Campus::city() }}, {{ now()->locale('id')->translatedFormat('j F Y') }}<br>
            {{ $internship->isSeminar() ? 'Dosen Pembimbing' : 'Pembimbing Industri' }}<br>
            {{ $internship->isSeminar() ? '' : ($internship->supervisor_title ?? '') }}
            <div class="space"></div>
            ({{ $internship->isSeminar() ? ($internship->lecturer?->name ?? '..............................') : ($internship->supervisor?->name ?? $internship->supervisor_name ?? '..............................') }})
        </div>

        @if ($report['tasks']->isNotEmpty())
            <h2 class="page-break">Daftar task per proyek</h2>
            @foreach ($byProject as $project => $tasks)
                <p><strong>{{ $project }}</strong> · {{ $tasks->count() }} task, {{ $tasks->where('status', 'completed')->count() }} selesai</p>
                <table class="grid" style="margin-bottom: 14px">
                    <thead>
                        <tr><th style="width: 12%">Kode</th><th>Task</th><th style="width: 16%">Status</th><th style="width: 14%">Tenggat</th><th style="width: 9%">Jam</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr>
                                <td>{{ $task->key() }}</td>
                                <td>{{ $task->title }}</td>
                                <td>{{ $task->column?->name ?? $task->statusLabel() }}</td>
                                <td>{{ $task->due_date?->format('d-m-Y') ?? '–' }}</td>
                                <td class="num">{{ \App\Support\Num::short((float) ($task->logged_hours ?? 0)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endif
    </div>
</body>
</html>
