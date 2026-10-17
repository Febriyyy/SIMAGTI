@props(['user'])
<div class="person">
    <span class="person-name">{{ $user->name }}</span>
    @if ($user->nim)
        <span class="person-sub">{{ $user->nim }}</span>
    @endif
</div>
