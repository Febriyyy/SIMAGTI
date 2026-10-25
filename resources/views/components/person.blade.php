{{-- Orang di daftar/tabel: nama ringkas (nama lengkap di tooltip dan halaman detail). --}}
@props(['user', 'avatar' => true])
<div class="person">
    @if ($avatar)
        <x-avatar :user="$user" small />
    @endif
    <div class="person-text">
        <span class="person-name" title="{{ $user->name }}">{{ $user->listName() }}</span>
        @if ($user->nim)
            <span class="person-sub">{{ $user->nim }}</span>
        @endif
    </div>
</div>
