{{-- Linimasa aktivitas. Variabel: $days (per tanggal), $showStudent (bool) --}}
<div class="days">
    @foreach ($days as $date => $items)
        @php($day = \Illuminate\Support\Carbon::parse($date)->locale('id'))
        <div class="day">
            <div class="day-head">
                <span class="day-date"><strong>{{ $day->isToday() ? 'Hari ini' : ($day->isYesterday() ? 'Kemarin' : $day->translatedFormat('l')) }}</strong>, {{ $day->translatedFormat('j F Y') }}</span>
                <span class="day-total">{{ $items->count() }} aktivitas</span>
            </div>
            <ul class="feed">
                @foreach ($items as $item)
                    @php($log = $item['log'])
                    <li class="feed-item">
                        <span class="feed-icon feed-{{ $item['kind'] }}"><x-ikon :name="$item['icon']" :size="16" /></span>
                        <div class="feed-body">
                            <a href="{{ $item['url'] }}" class="feed-text">{{ $log->description }}</a>
                            <span class="feed-meta">
                                @if ($showStudent)<span title="{{ $log->user?->name }}">{{ $log->user?->listName() }}</span> · @endif
                                {{ $item['label'] }} · {{ $log->created_at->format('H:i') }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
