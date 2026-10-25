@extends('layouts.app')

@section('title', 'Pengajuan Perubahan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Pengajuan Perubahan</h1>
            <p class="page-sub">Perpanjangan, pindah, mundur, atau ganti supervisor.</p>
        </div>
    </div>

    @if (! $internship)
        <div class="card"><div class="empty"><strong>Belum ada KP berjalan</strong> Pengajuan perubahan tersedia selama KP berjalan.</div></div>
    @else
        <div class="layout">
            <div class="col">
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Riwayat pengajuan</h2></div>
                    @if ($history->isEmpty())
                        <p class="meeting-empty">Belum ada pengajuan perubahan.</p>
                    @else
                        <ul class="item-list">
                            @foreach ($history as $c)
                                <li class="item-row change">
                                    <span class="item-icon tone-bg-{{ $c->statusTone() }}"><x-ikon name="swap" :size="18" /></span>
                                    <div class="grow min0">
                                        <div class="cell-title">{{ $c->typeLabel() }}</div>
                                        <div class="cell-sub">{{ $c->created_at->locale('id')->translatedFormat('j M Y') }}</div>
                                    </div>
                                    <x-chip :tone="$c->statusTone()">{{ $c->statusLabel() }}</x-chip>
                                    <x-detail>
                                        <dl>
                                            <div><dt>Perubahan</dt><dd>{{ $c->summary() }}</dd></div>
                                            <div><dt>Alasan</dt><dd>{{ $c->reason }}</dd></div>
                                            @if ($c->supervisor_note)<div><dt>Company Supervisor</dt><dd>{{ $c->supervisor_note }}</dd></div>@endif
                                            @if ($c->decision_note)<div><dt>Keputusan</dt><dd>{{ $c->decision_note }} — {{ $c->decider?->name }}</dd></div>@endif
                                        </dl>
                                    </x-detail>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <div class="col">
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Ajukan perubahan</h2></div>
                    <div class="card-body">
                        @if (! $canRequest)
                            <p class="muted">{{ $internship->status === 'berjalan' ? 'Masih ada pengajuan yang sedang diproses.' : 'KP ini sudah ditutup.' }}</p>
                        @else
                            <form method="POST" action="{{ route('perubahan.store') }}" class="stack" data-change-form>
                                @csrf
                                <div class="field">
                                    <label class="label" for="type">Jenis perubahan</label>
                                    <select id="type" name="type" class="select" required data-type>
                                        @foreach (\App\Models\ChangeRequest::TYPES as $k => [$label, $desc])
                                            @continue($k === 'replace_supervisor' && $internship->isSeminar())
                                            <option value="{{ $k }}" @selected(old('type') === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div data-show="extend" class="stack">
                                    <div class="field">
                                        <label class="label" for="new_end_date">Tanggal selesai baru</label>
                                        <input id="new_end_date" type="date" name="new_end_date" class="input" value="{{ old('new_end_date') }}"
                                               min="{{ $internship->end_date->copy()->addDay()->toDateString() }}" max="{{ $internship->end_date->copy()->addMonths(6)->toDateString() }}">
                                    </div>
                                    <p class="hint">Sekarang selesai {{ $internship->end_date->locale('id')->translatedFormat('j F Y') }}. {{ $internship->isSeminar() ? 'Diputuskan kaprodi.' : 'Company Supervisor menyatakan setuju, lalu diputuskan kaprodi.' }}</p>
                                </div>

                                <div data-show="transfer" class="stack">
                                    <div class="field">
                                        <label class="label" for="company_name">Tempat baru (bila sudah ada)</label>
                                        <input id="company_name" type="text" name="company_name" class="input" maxlength="150" value="{{ old('company_name') }}">
                                        <p class="hint">Setelah disetujui kaprodi, KP ini ditutup dengan status "Pindah" dan Anda mengajukan KP baru dari Status KP.</p>
                                    </div>
                                </div>

                                <div data-show="withdraw">
                                    <p class="hint">Setelah disetujui kaprodi, KP ini dibatalkan.</p>
                                </div>

                                @unless ($internship->isSeminar())
                                    <div data-show="replace_supervisor" class="stack">
                                        <div class="field">
                                            <label class="label" for="supervisor_name">Nama supervisor baru</label>
                                            <input id="supervisor_name" type="text" name="supervisor_name" class="input" maxlength="150" value="{{ old('supervisor_name') }}">
                                        </div>
                                        <div class="field">
                                            <label class="label" for="supervisor_title">Jabatan</label>
                                            <input id="supervisor_title" type="text" name="supervisor_title" class="input" maxlength="100" value="{{ old('supervisor_title') }}">
                                        </div>
                                        <div class="field">
                                            <label class="label" for="supervisor_email">Email kantor</label>
                                            <input id="supervisor_email" type="email" name="supervisor_email" class="input" maxlength="150" value="{{ old('supervisor_email') }}">
                                            <p class="hint">TU Fakultas memproses lalu mengaktifkan akun supervisor baru.</p>
                                        </div>
                                    </div>
                                @endunless

                                <div class="field">
                                    <label class="label" for="reason">Alasan</label>
                                    <textarea id="reason" name="reason" class="textarea" rows="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                                </div>
                                <div class="row-actions"><button type="submit" class="btn btn-primary"><x-ikon name="send" :size="18" /> Kirim pengajuan</button></div>
                            </form>
                            <script>
                                (function () {
                                    var form = document.querySelector('[data-change-form]');
                                    var type = form.querySelector('[data-type]');
                                    function show() {
                                        form.querySelectorAll('[data-show]').forEach(function (el) {
                                            var on = el.dataset.show === type.value;
                                            el.hidden = !on;
                                            el.querySelectorAll('input').forEach(function (i) { i.disabled = !on; });
                                        });
                                    }
                                    type.addEventListener('change', show);
                                    show();
                                })();
                            </script>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    @endif
@endsection
