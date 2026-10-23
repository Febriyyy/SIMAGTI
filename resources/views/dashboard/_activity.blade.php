{{-- Grafik garis: jam kegiatan (timesheet) per minggu. Variabel: $activity --}}
@php($weeks = \App\Actions\Dashboard\BuildMonitoringDashboard::ACTIVITY_WEEKS)
<section class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Jam kegiatan per minggu</h2>
            <p class="card-sub">{{ $weeks }} minggu terakhir, dari timesheet semua mahasiswa di daftar atas</p>
        </div>
        <x-chip tone="green">{{ \App\Support\Num::short(array_sum($activity['values'])) }} jam</x-chip>
    </div>
    <div class="card-body">
        <x-line-chart :labels="$activity['labels']" :tips="$activity['ranges']" unit="jam"
                      :series="[['name' => 'Jam tercatat', 'values' => $activity['values'], 'color' => 'green', 'area' => true]]" />
        <p class="chart-note">Arahkan kursor ke grafik untuk melihat jumlah per minggu.</p>
    </div>
</section>
