{{--
    Jadwal task di dashboard mahasiswa: kalender Senin–Minggu (tanpa jam), task sebagai blok yang
    membentang dari tanggal mulai sampai tenggat; hari ini disorot. Kartu per rencana ada di tab Rencana Timesheet.
    Variabel: $week (App\Support\WeekPlanner::build)
--}}
<section class="card week-card" aria-labelledby="week-title">
    <div class="card-head">
        <div>
            <h2 class="card-title" id="week-title">Jadwal task <span class="week-cal-count">{{ count($week['items']) }}</span></h2>
        </div>
        <div class="week-nav">
            <a href="{{ route('dashboard', ['minggu' => $week['offset'] - 1]) }}#week-title" class="btn btn-sm btn-icon" aria-label="Minggu sebelumnya"><x-ikon name="back" :size="16" /></a>
            <span class="week-label">{{ $week['offset'] === 0 ? 'Minggu ini' : $week['label'] }}</span>
            <a href="{{ route('dashboard', ['minggu' => $week['offset'] + 1]) }}#week-title" class="btn btn-sm btn-icon" aria-label="Minggu berikutnya"><x-ikon name="next" :size="16" /></a>
            <a href="{{ route('timesheet.index') }}" class="btn btn-sm">Timesheet</a>
        </div>
    </div>

    @include('timesheet._week-cal', ['week' => $week])
</section>
