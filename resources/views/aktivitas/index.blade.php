@extends('layouts.app')

@section('title', 'Aktivitas')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Aktivitas</h1>
            <p class="page-sub">Apa yang dikerjakan mahasiswa, terbaru di atas.</p>
        </div>
    </div>

    <form method="GET" class="filters" data-autosubmit>
        <select name="mahasiswa" class="select select-sm" aria-label="Mahasiswa">
            <option value="">Semua mahasiswa</option>
            @foreach ($internships as $i)
                <option value="{{ $i->student_id }}" @selected($studentId === $i->student_id)>{{ $i->student->listName() }}</option>
            @endforeach
        </select>
        <select name="jenis" class="select select-sm" aria-label="Jenis aktivitas">
            <option value="">Semua jenis</option>
            @foreach (\App\Support\ActivityFeed::KINDS as $key => [$label])
                <option value="{{ $key }}" @selected($kind === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="segmented" role="group" aria-label="Rentang waktu">
            @foreach ([1 => 'Hari ini', 7 => '7 hari', 30 => '30 hari'] as $value => $label)
                <a href="{{ request()->fullUrlWithQuery(['hari' => $value]) }}" @class(['seg', 'is-active' => $range === $value])>{{ $label }}</a>
            @endforeach
        </div>
        <input type="hidden" name="hari" value="{{ $range }}">
    </form>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Linimasa</h2>
                        <p class="card-sub">{{ $feed->count() }} aktivitas · {{ $range === 1 ? 'hari ini' : $range.' hari terakhir' }}</p>
                    </div>
                </div>
                @if ($feed->isEmpty())
                    <div class="empty">
                        <strong>Belum ada aktivitas</strong>
                        {{ $internships->isEmpty() ? 'Belum ada mahasiswa yang Anda pantau.' : 'Coba rentang waktu yang lebih panjang.' }}
                    </div>
                @else
                    @include('aktivitas._feed', ['showStudent' => true])
                @endif
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Ringkasan</h2>
                        <p class="card-sub">Per jenis aktivitas</p>
                    </div>
                </div>
                <ul class="item-list">
                    @foreach (\App\Support\ActivityFeed::KINDS as $key => [$label, $icon])
                        <li class="item-row">
                            <span class="feed-icon feed-{{ $key }}"><x-ikon :name="$icon" :size="16" /></span>
                            <a href="{{ request()->fullUrlWithQuery(['jenis' => $key]) }}" class="grow cell-title">{{ $label }}</a>
                            <span class="num">{{ $feed->where('kind', $key)->count() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            @if ($quiet->isNotEmpty())
                <section class="card">
                    <div class="card-head">
                        <div>
                            <h2 class="card-title">Belum ada aktivitas</h2>
                            <p class="card-sub">{{ $range === 1 ? 'Hari ini' : $range.' hari terakhir' }}</p>
                        </div>
                        <x-chip tone="amber">{{ $quiet->count() }}</x-chip>
                    </div>
                    <ul class="item-list">
                        @foreach ($quiet as $i)
                            <li class="item-row">
                                <x-avatar :user="$i->student" small />
                                <div class="grow min0">
                                    <div class="cell-title" title="{{ $i->student->name }}">{{ $i->student->listName() }}</div>
                                    <div class="cell-sub">{{ $i->placeName() }}</div>
                                </div>
                                <a href="{{ route('pantau.show', $i) }}" class="btn btn-quiet">Buka</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
@endsection
