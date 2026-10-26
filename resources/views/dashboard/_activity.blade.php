{{-- Grafik garis: catatan kegiatan dan task selesai per minggu. Variabel: $activity --}}
@php($weeks = \App\Actions\Dashboard\BuildMonitoringDashboard::ACTIVITY_WEEKS)
<section class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Aktivitas per minggu</h2>
            <p class="card-sub">{{ $weeks }} minggu terakhir, semua mahasiswa di daftar atas</p>
        </div>
        <a href="{{ route('aktivitas.index') }}" class="btn btn-sm">Aktivitas</a>
    </div>
    <div class="card-body">
        <x-line-chart :labels="$activity['labels']" :tips="$activity['ranges']" unit="kali"
                      :series="[
                          ['name' => 'Catatan kegiatan', 'values' => $activity['notes'], 'color' => 'green', 'area' => true],
                          ['name' => 'Task selesai', 'values' => $activity['done'], 'color' => 'blue'],
                      ]" />
        <p class="chart-note">Arahkan kursor ke grafik untuk melihat jumlah per minggu.</p>
    </div>
</section>
