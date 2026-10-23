@props(['user', 'avatar' => true])
<div class="person">
    @if ($avatar)
        <x-avatar :user="$user" small />
    @endif
    <div class="person-text">
        <span class="person-name" title="{{ $user->name }}">{{ $user->name }}</span>
        @if ($user->nim)
            <span class="person-sub">{{ $user->nim }}</span>
        @endif
    </div>
</div>
