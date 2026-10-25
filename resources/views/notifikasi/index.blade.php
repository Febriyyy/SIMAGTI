@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-sub">Tindakan dan kabar terbaru.</p>
        </div>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Perlu tindakan</h2>
                        <p class="card-sub">Hilang sendiri setelah ditindaklanjuti</p>
                    </div>
                    <x-chip :tone="$actions === [] ? 'green' : 'amber'">{{ count($actions) }}</x-chip>
                </div>
                @if ($actions === [])
                    <div class="calm">
                        <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
                        <p>Tidak ada yang perlu ditindaklanjuti saat ini.</p>
                    </div>
                @else
                    <ul class="item-list">
                        @foreach ($actions as $item)
                            <li>
                                <a href="{{ $item['url'] }}" class="item-row row-link">
                                    <span class="item-icon tone-bg-{{ $item['tone'] === 'red' ? 'red' : 'amber' }}"><x-ikon :name="$item['icon']" :size="18" /></span>
                                    <div class="grow">
                                        <div class="cell-title">{{ $item['title'] }}</div>
                                        @if ($item['detail'])<div class="cell-sub">{{ $item['detail'] }}</div>@endif
                                    </div>
                                    <x-ikon name="arrow" :size="18" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Kabar terbaru</h2>
                </div>
                @if ($notifications->isEmpty())
                    <p class="meeting-empty">Belum ada kabar.</p>
                @else
                    <ul class="item-list">
                        @foreach ($notifications as $n)
                            <li>
                                <a href="{{ route('notifikasi.open', $n->id) }}" class="item-row row-link">
                                    <span class="item-icon tone-bg-{{ $n->data['tone'] ?? 'blue' }}"><x-ikon :name="$n->data['icon'] ?? 'mail'" :size="18" /></span>
                                    <div class="grow">
                                        <div class="cell-title">{{ $n->data['title'] ?? '' }}</div>
                                        @if (! empty($n->data['body']))<div class="cell-sub">{{ $n->data['body'] }}</div>@endif
                                        <div class="cell-sub">{{ $n->created_at->locale('id')->diffForHumans() }}</div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
