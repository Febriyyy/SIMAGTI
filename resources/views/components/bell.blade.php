{{-- Lonceng notifikasi di topbar: hal yang perlu ditindaklanjuti + kabar yang belum dibaca. --}}
@php
    $bellUser = auth()->user();
    $bellActions = \App\Support\ActionItems::for($bellUser);
    $bellUnread = $bellUser->unreadNotifications()->latest()->limit(5)->get();
    $bellCount = count($bellActions) + $bellUser->unreadNotifications()->count();
@endphp
<details class="menu bell">
    <summary class="bell-btn" aria-label="Notifikasi{{ $bellCount ? ', '.$bellCount.' baru' : '' }}">
        <x-ikon name="bell" :size="20" />
        @if ($bellCount > 0)
            <span class="bell-count">{{ $bellCount > 9 ? '9+' : $bellCount }}</span>
        @endif
    </summary>
    <div class="menu-panel bell-panel">
        <div class="bell-head">
            <strong>Notifikasi</strong>
            <a href="{{ route('notifikasi.index') }}">Lihat semua</a>
        </div>
        @if ($bellActions === [] && $bellUnread->isEmpty())
            <p class="bell-empty"><x-ikon name="check" :size="18" /> Tidak ada yang perlu ditindaklanjuti.</p>
        @endif
        @if ($bellActions !== [])
            <p class="bell-section">Perlu tindakan</p>
            @foreach (array_slice($bellActions, 0, 6) as $item)
                <a href="{{ $item['url'] }}" class="bell-item">
                    <span class="bell-icon tone-bg-{{ $item['tone'] === 'red' ? 'red' : 'amber' }}"><x-ikon :name="$item['icon']" :size="16" /></span>
                    <span class="bell-text">
                        <span class="bell-title">{{ $item['title'] }}</span>
                        @if ($item['detail'])<span class="bell-detail">{{ $item['detail'] }}</span>@endif
                    </span>
                </a>
            @endforeach
            @if (count($bellActions) > 6)
                <a href="{{ route('notifikasi.index') }}" class="bell-more">+{{ count($bellActions) - 6 }} lainnya</a>
            @endif
        @endif
        @if ($bellUnread->isNotEmpty())
            <p class="bell-section">Kabar baru</p>
            @foreach ($bellUnread as $n)
                <a href="{{ route('notifikasi.open', $n->id) }}" class="bell-item">
                    <span class="bell-icon tone-bg-{{ $n->data['tone'] ?? 'blue' }}"><x-ikon :name="$n->data['icon'] ?? 'mail'" :size="16" /></span>
                    <span class="bell-text">
                        <span class="bell-title">{{ $n->data['title'] ?? '' }}</span>
                        <span class="bell-detail">{{ $n->created_at->locale('id')->diffForHumans() }}</span>
                    </span>
                </a>
            @endforeach
        @endif
    </div>
</details>
