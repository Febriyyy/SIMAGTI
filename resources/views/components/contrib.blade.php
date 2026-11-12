{{--
    Peta kontribusi ala GitHub (App\Support\Contribution::build). Satu kotak per hari; warna makin pekat = makin banyak
    catatan + task selesai. Kotak bisa difokus dan punya keterangan (title + aria-label).
    Variabel: data (hasil build), title (opsional)
--}}
@props(['data', 'title' => 'Peta kontribusi'])
<section {{ $attributes->class(['card', 'contrib']) }} aria-labelledby="contrib-title">
    <div class="card-head">
        <h2 class="card-title" id="contrib-title">{{ $title }}</h2>
        <ul class="contrib-stats">
            <li><b>{{ $data['activeDays'] }}</b> hari aktif</li>
            <li><b>{{ $data['longest'] }}</b> hari beruntun terpanjang</li>
            <li><b>{{ $data['current'] }}</b> hari beruntun sekarang</li>
        </ul>
    </div>
    <div class="contrib-body">
        <div class="contrib-scroll">
            <div class="contrib-grid" style="--weeks: {{ count($data['weeks']) }}">
                <div class="contrib-months" aria-hidden="true">
                    @foreach ($data['months'] as $m)<span style="--col: {{ $m['col'] }}">{{ $m['label'] }}</span>@endforeach
                </div>
                <div class="contrib-days" aria-hidden="true"><span>Sen</span><span></span><span>Rab</span><span></span><span>Jum</span><span></span><span></span></div>
                <div class="contrib-cells" role="list" aria-label="Kegiatan harian selama KP">
                    @foreach ($data['weeks'] as $week)
                        @foreach ($week as $day)
                            @if ($day === null)
                                <i class="contrib-cell is-out" aria-hidden="true"></i>
                            @else
                                @php($label = $day['date']->locale('id')->translatedFormat('l, j M Y').': '.($day['future'] ? 'belum berlangsung' : ($day['count'] ? $day['count'].' kegiatan' : 'tidak ada kegiatan')))
                                <i role="listitem" tabindex="-1" @class(['contrib-cell', 'l'.$day['level'], 'is-future' => $day['future']]) title="{{ $label }}" aria-label="{{ $label }}"></i>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
        <div class="contrib-legend" aria-hidden="true">
            <span>Sedikit</span><i class="contrib-cell"></i><i class="contrib-cell l1"></i><i class="contrib-cell l2"></i><i class="contrib-cell l3"></i><i class="contrib-cell l4"></i><span>Banyak</span>
        </div>
    </div>
</section>
