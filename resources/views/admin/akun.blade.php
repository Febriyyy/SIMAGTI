@extends('layouts.app')

@section('title', 'Akun')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Akun</h1>
            <p class="page-sub">Ubah peran dan aktifkan atau nonaktifkan akun.</p>
        </div>
    </div>

    <div class="chip-filters">
        <a href="{{ route('admin.akun', array_filter(['status' => $status, 'cari' => $term ?: null])) }}" @class(['seg-chip', 'is-active' => ! $role])>Semua <span>{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\User::ROLE_LABELS as $key => $label)
            <a href="{{ route('admin.akun', array_filter(['peran' => $key, 'status' => $status, 'cari' => $term ?: null])) }}" @class(['seg-chip', 'is-active' => $role === $key])>{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>

    <form method="GET" class="filters" data-autosubmit>
        <div class="filter-search">
            <x-ikon name="search" :size="16" />
            <input type="search" name="cari" value="{{ $term }}" placeholder="Cari nama, email, atau NIM…" aria-label="Cari akun" class="input input-sm">
        </div>
        <select name="status" class="select select-sm" aria-label="Status akun">
            <option value="">Semua status</option>
            <option value="aktif" @selected($status === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
        </select>
        @if ($role)<input type="hidden" name="peran" value="{{ $role }}">@endif
    </form>

    <section class="card">
        @if ($users->isEmpty())
            <div class="empty"><strong>Tidak ada akun</strong> Coba kata kunci atau filter lain.</div>
        @else
            <div class="table-wrap">
                <table class="table dt">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th class="w-role">Peran</th>
                            <th class="w-status">Status</th>
                            <th class="w-action" aria-label="Aksi"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $account)
                            @php($self = $account->is(auth()->user()))
                            <tr @class(['is-muted' => ! $account->is_active])>
                                <td>
                                    <span class="ident">
                                        <x-avatar :user="$account" />
                                        <span>
                                            <span class="c-main" title="{{ $account->name }}">{{ $account->listName() }}@if ($self) <span class="muted">(Anda)</span>@endif</span>
                                            <span class="c-sub" title="{{ $account->email }}">{{ $account->nim ? $account->nim.' · ' : '' }}{{ $account->email }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    @if ($self)
                                        <span class="c-main c-plain">{{ $account->roleLabel() }}</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.akun.update', $account) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" class="select select-sm" onchange="this.form.submit()" aria-label="Peran {{ $account->name }}">
                                                @foreach (\App\Models\User::ROLE_LABELS as $key => $label)
                                                    <option value="{{ $key }}" @selected($account->role === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif
                                </td>
                                <td><x-chip :tone="$account->is_active ? 'green' : 'gray'">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-chip></td>
                                <td class="act">
                                    @unless ($self)
                                        <form method="POST" action="{{ route('admin.akun.update', $account) }}"
                                              @if ($account->is_active) onsubmit="return confirm('Nonaktifkan akun {{ $account->listName() }}? Akun tidak bisa masuk sampai diaktifkan lagi.')" @endif>
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $account->is_active ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-quiet">{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="card-foot pager">
                    <span>{{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} akun</span>
                    <span class="row-actions">
                        @if ($users->previousPageUrl())<a href="{{ $users->previousPageUrl() }}" class="btn btn-sm">Sebelumnya</a>@endif
                        @if ($users->nextPageUrl())<a href="{{ $users->nextPageUrl() }}" class="btn btn-sm">Berikutnya</a>@endif
                    </span>
                </div>
            @endif
        @endif
    </section>
@endsection
