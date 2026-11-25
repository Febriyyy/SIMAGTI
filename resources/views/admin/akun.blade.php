@extends('layouts.app')

@section('title', 'Akun')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Akun</h1>
            <p class="page-sub">Peran, status, dan kata sandi akun.</p>
        </div>
        <button type="button" class="btn btn-primary" data-open="dlg-account"><x-ikon name="plus" :size="16" /> Tambah akun</button>
    </div>

    @if (session('temporary_password'))
        @php($temp = session('temporary_password'))
        <section class="card callout" aria-live="polite">
            <div class="card-body pt">
                <label for="temp-password" class="label">Kata sandi sementara untuk {{ $temp['name'] }}</label>
                <div class="inline-form">
                    <input id="temp-password" type="text" class="input temp-password" readonly value="{{ $temp['password'] }}" onclick="this.select()">
                    <button type="button" class="btn" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('temp-password').value); this.textContent='Tersalin'"><x-ikon name="copy" :size="18" /> Salin</button>
                </div>
                <p class="hint">Untuk {{ $temp['email'] }}. Hanya tampil sekali.</p>
            </div>
        </section>
    @endif

    <div class="chip-filters">
        <a href="{{ route('admin.akun', array_filter(['status' => $status, 'cari' => $term ?: null, 'urut' => $sort])) }}" @class(['seg-chip', 'is-active' => ! $role])>Semua <span>{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\User::ROLE_LABELS as $key => $label)
            <a href="{{ route('admin.akun', array_filter(['peran' => $key, 'status' => $status, 'cari' => $term ?: null, 'urut' => $sort])) }}" @class(['seg-chip', 'is-active' => $role === $key])>{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
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
        <select name="urut" class="select select-sm" aria-label="Urutan akun">
            <option value="">Urut per peran</option>
            <option value="terbaru" @selected($sort === 'terbaru')>Terbaru dibuat</option>
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
                            <th class="w-action-2" aria-label="Aksi"></th>
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
                                            @if ($sort === 'terbaru' && $account->created_at)
                                                <span class="c-sub" title="{{ $account->created_at->locale('id')->translatedFormat('j F Y, H.i') }}">Dibuat {{ $account->created_at->locale('id')->diffForHumans() }}</span>
                                            @endif
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
                                        <form method="POST" action="{{ route('admin.akun.sandi', $account) }}"
                                              onsubmit="return confirm('Atur ulang kata sandi {{ $account->listName() }}? Akun ini akan keluar dari semua perangkat.')">
                                            @csrf
                                            <button type="submit" class="btn btn-quiet">Atur ulang sandi</button>
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

    {{-- Dialog tambah akun staf kampus (mahasiswa ditambah kaprodi, Company Supervisor lewat undangan TU) --}}
    <?php $ab = $errors->getBag('account'); ?>
    <dialog id="dlg-account" class="dialog" @if ($ab->any()) data-autoopen @endif>
        <form method="POST" action="{{ route('admin.akun.store') }}" class="dialog-body" data-account-form>
            @csrf
            <div class="dialog-head">
                <div>
                    <h2 class="card-title">Tambah akun</h2>
                    <p class="card-sub">Kata sandi sementara dibuat otomatis</p>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
            </div>
            <div class="field">
                <label class="label" for="acc-name">Nama lengkap</label>
                <input id="acc-name" name="name" class="input" maxlength="120" required autofocus value="{{ old('name') }}" placeholder="Dr. Nama Lengkap, S.Kom., M.Kom.">
                @if ($ab->has('name'))<p class="field-error">{{ $ab->first('name') }}</p>@endif
            </div>
            <div class="form-grid">
                <div class="field">
                    <label class="label" for="acc-email">Email</label>
                    <input id="acc-email" name="email" type="email" class="input" maxlength="150" required value="{{ old('email') }}" placeholder="nama@kampus.ac.id" autocomplete="off">
                    @if ($ab->has('email'))<p class="field-error">{{ $ab->first('email') }}</p>@endif
                </div>
                <div class="field">
                    <label class="label" for="acc-role">Peran</label>
                    <select id="acc-role" name="role" class="select" required data-account-role>
                        @foreach (\App\Actions\Accounts\CreateAccount::ROLES as $r)
                            <option value="{{ $r }}" @selected(old('role', 'dospem') === $r)>{{ \App\Models\User::ROLE_LABELS[$r] }}</option>
                        @endforeach
                    </select>
                    @if ($ab->has('role'))<p class="field-error">{{ $ab->first('role') }}</p>@endif
                </div>
            </div>
            <div class="form-grid" data-account-lecturer>
                <div class="field">
                    <label class="label" for="acc-prodi">Program studi</label>
                    <select id="acc-prodi" name="study_program_id" class="select">
                        <option value="">Semua prodi</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}" @selected((string) old('study_program_id') === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @if ($ab->has('study_program_id'))<p class="field-error">{{ $ab->first('study_program_id') }}</p>@endif
                </div>
                <div class="field">
                    <label class="label" for="acc-nidn">NIDN <span class="label-opt">(opsional)</span></label>
                    <input id="acc-nidn" name="nidn" class="input" maxlength="20" inputmode="numeric" value="{{ old('nidn') }}">
                    @if ($ab->has('nidn'))<p class="field-error">{{ $ab->first('nidn') }}</p>@endif
                </div>
            </div>
            <div class="dialog-foot row-actions">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" class="btn btn-primary">Buat akun</button>
            </div>
        </form>
    </dialog>

    <script>
        // Prodi dan NIDN hanya untuk dosen pembimbing dan kaprodi
        (function () {
            var role = document.querySelector('[data-account-role]');
            var box = document.querySelector('[data-account-lecturer]');
            if (!role || !box) return;
            var sync = function () { box.hidden = ['dospem', 'kaprodi'].indexOf(role.value) < 0; };
            role.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
