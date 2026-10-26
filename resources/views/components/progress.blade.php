{{-- Bar progres. inside: angka persen di dalam batang --}}
@props(['value' => 0, 'inside' => false])
@php($value = max(0, min(100, (int) $value)))
@if ($inside)
    {{-- Angka di ujung bagian terisi (putih); bila terlalu pendek, angka di luar batang (abu-abu) --}}
    <div class="progress-in" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $value }}% selesai">
        <span style="width: {{ $value }}%">@if ($value >= 28)<b>{{ $value }}%</b>@endif</span>
        @if ($value < 28)<b class="is-out">{{ $value }}%</b>@endif
    </div>
@else
<div class="progress-cell">
    <div class="progress" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
        <span style="width: {{ $value }}%"></span>
    </div>
    <span class="progress-label">{{ $value }}%</span>
</div>
@endif
