{{--
    Kartu "Perlu perhatian" (dashboard dosen pembimbing dan kaprodi).
    Satu baris per mahasiswa: avatar, nama, tempat, chip alasan berwarna (kalimat lengkap di tooltip), panah ke halaman pantau.
    Variabel: $attention (dari BuildMonitoringDashboard), $rules (kelas BuildMonitoringDashboard untuk ambang aturan)
--}}
<section class="card attn" id="perlu-perhatian">
    <div class="card-head attn-head">
        <div class="attn-title">
            <span @class(['attn-icon', 'is-calm' => $attention->isEmpty()]) aria-hidden="true">
                <x-ikon :name="$attention->isEmpty() ? 'check' : 'alert'" :size="18" />
            </span>
            <div>
                <h2 class="card-title">Perlu perhatian</h2>
                <p class="card-sub">Sepi, progres rendah, atau hampir selesai</p>
            </div>
        </div>
        @if ($attention->isNotEmpty())
            <span class="attn-count" aria-label="{{ $attention->count() }} mahasiswa perlu perhatian">{{ $attention->count() }}</span>
        @endif
    </div>

    @if ($attention->isEmpty())
        <div class="calm">
            <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
            <p>Semua mahasiswa aman.</p>
        </div>
    @else
        <ul class="attn-list">
            @foreach ($attention->take(5) as $row)
                @php($i = $row['internship'])
                <li class="attn-row">
                    <x-avatar :user="$i->student" />
                    <div class="attn-who">
                        <a href="{{ route('pantau.show', $i) }}" class="attn-name" title="{{ $i->student->name }}">{{ $i->student->listName() }}</a>
                        <span class="attn-place">{{ $i->placeName() }}</span>
                    </div>
                    <ul class="attn-chips" aria-label="Alasan">
                        @foreach ($row['reasons'] as $reason)
                            <?php
                                [$kind, $icon, $short] = match (true) {
                                    str_starts_with($reason, 'Belum pernah') => ['idle', 'clock', 'Belum mencatat'],
                                    (bool) preg_match('/kegiatan baru (\d+) hari/', $reason, $m) => ['idle', 'clock', $m[1].' hari tanpa kegiatan'],
                                    (bool) preg_match('/Progres task (\d+)%/', $reason, $m) => ['low', 'chart', 'Progres '.$m[1].'%'],
                                    (bool) preg_match('/berakhir (\d+) hari/', $reason, $m) => ['end', 'timer', $m[1] === '0' ? 'Berakhir hari ini' : 'Berakhir '.$m[1].' hari lagi'],
                                    default => ['idle', 'alert', $reason],
                                };
                            ?>
                            <li class="attn-chip is-{{ $kind }}" title="{{ $reason }}">
                                <x-ikon :name="$icon" :size="14" /> {{ $short }}<span class="sr-only">: {{ $reason }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <span class="attn-go" aria-hidden="true"><x-ikon name="next" :size="18" /></span>
                </li>
            @endforeach
        </ul>
        @if ($attention->count() > 5)
            <a href="{{ route('pantau.index') }}" class="attn-more">+{{ $attention->count() - 5 }} mahasiswa lainnya</a>
        @endif
    @endif
</section>
