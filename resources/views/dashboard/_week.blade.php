{{--
    Rencana kegiatan di dashboard mahasiswa (acuan: kalender mingguan berwarna).
    Atas: kartu berwarna per rencana dengan progres task. Bawah: kalender Senin–Minggu (tanpa jam),
    task sebagai blok berwarna sesuai rencana, membentang dari tanggal mulai sampai tenggat; hari ini disorot.
    Variabel: $plans, $week (App\Support\WeekPlanner::build)
--}}
<section class="card week-card" aria-labelledby="week-title">
    <div class="card-head">
        <div>
            <h2 class="card-title" id="week-title">Rencana kegiatan</h2>
        </div>
        <div class="week-nav">
            <a href="{{ route('dashboard', ['minggu' => $week['offset'] - 1]) }}#week-title" class="btn btn-sm btn-icon" aria-label="Minggu sebelumnya"><x-ikon name="back" :size="16" /></a>
            <span class="week-label">{{ $week['offset'] === 0 ? 'Minggu ini' : $week['label'] }}</span>
            <a href="{{ route('dashboard', ['minggu' => $week['offset'] + 1]) }}#week-title" class="btn btn-sm btn-icon" aria-label="Minggu berikutnya"><x-ikon name="next" :size="16" /></a>
            <a href="{{ route('timesheet.index') }}" class="btn btn-sm">Timesheet</a>
        </div>
    </div>

    @include('timesheet._plan-cards', ['plans' => $plans])

    @include('timesheet._week-cal', ['week' => $week])
</section>
