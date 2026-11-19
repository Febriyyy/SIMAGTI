{{--
    Rincian nilai laporan akhir dari dosen pembimbing (hanya baca), sama polanya dengan rincian Company Supervisor.
    Variabel: $internship, $report (ReportScore|null), $canGrade (dosen pembimbing boleh menilai)
--}}
@if ($report)
    <details class="rate-group rate-fold">
        <summary class="rate-head">
            <div>
                <h3 class="rate-title">Laporan akhir</h3>
                <p class="rate-sub">4 aspek · dinilai {{ $report->graded_at?->locale('id')->translatedFormat('j M Y') ?? '–' }}</p>
            </div>
            <div class="rate-score">
                <strong>{{ $report->total }}</strong>
                <small>dari 100</small>
            </div>
            <span class="rate-chev" aria-hidden="true"><x-ikon name="expand" :size="20" /></span>
        </summary>
        <ul class="rate-list">
            @foreach (\App\Models\ReportScore::ASPECTS as $key => [$label, $max])
                <li class="rate-row">
                    <span class="rate-q">{{ $label }}</span>
                    <span class="rate-answer">{{ $report->{$key} }} <span class="muted">dari {{ $max }}</span></span>
                </li>
            @endforeach
        </ul>
    </details>
    <div class="rate-total">
        <span>Nilai dosen pembimbing</span>
        <strong>{{ $report->total }}</strong>
    </div>
    @if ($report->note)
        <div class="card-body pt">
            <h3 class="form-section-title">Catatan dosen pembimbing</h3>
            <p class="prewrap">{{ $report->note }}</p>
        </div>
    @endif
@else
    <div class="empty">
        <strong>Laporan akhir belum dinilai</strong>
        {{ $canGrade ? 'Beri nilai empat aspek laporan akhir di halaman Laporan KP mahasiswa.' : 'Dosen pembimbing belum menilai laporan akhir.' }}
        @if ($canGrade)
            <a href="{{ route('laporan.diskusi', $internship) }}#nilai" class="btn btn-sm" style="display: flex; width: fit-content; margin: 14px auto 0">Nilai laporan akhir</a>
        @endif
    </div>
@endif
