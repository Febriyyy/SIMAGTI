@props(['value' => 0])
<div class="progress-cell">
    <div class="progress" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
        <span style="width: {{ $value }}%"></span>
    </div>
    <span class="progress-label">{{ $value }}%</span>
</div>
