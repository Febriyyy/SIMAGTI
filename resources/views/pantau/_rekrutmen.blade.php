{{--
    Rekomendasi jadi karyawan di halaman detail mahasiswa. Company Supervisor mengelola langkahnya;
    dosen pembimbing dan kaprodi hanya melihat. Kartu tampil setelah ada rekomendasi.
--}}
@php
    $c = $internship->conversion;
    $isOwner = auth()->user()->role === 'supervisor' && $internship->isSupervisedBy(auth()->user());
    $at = fn (string $status) => $c?->histories->where('to_status', $status)->sortByDesc('created_at')->first()?->created_at;
    $final = $c && in_array($c->status, ['diterima', 'ditolak'], true);
    $steps = [
        ['direkomendasikan', 'Direkomendasikan'],
        ['ditawari', 'Ditawari kerja'],
        [$c?->status === 'ditolak' ? 'ditolak' : 'diterima', $c?->status === 'ditolak' ? 'Menolak' : 'Diterima'],
    ];
    $order = ['direkomendasikan' => 0, 'ditawari' => 1, 'diterima' => 2, 'ditolak' => 2];
    $reached = $c ? $order[$c->status] : -1;
@endphp
@if ($c)
    <section class="card recruit" id="rekrutmen">
        <div class="card-head">
            <div>
                <h2 class="card-title">Rekrutmen</h2>
                <p class="card-sub">{{ $c->offered_position ? 'Posisi '.$c->offered_position : 'Rekomendasi Company Supervisor' }}</p>
            </div>
            <x-chip :tone="$c->statusTone()">{{ $c->statusLabel() }}</x-chip>
        </div>
        <div class="card-body">
            <ol class="recruit-steps">
                @foreach ($steps as $n => [$key, $label])
                    <li @class(['is-done' => $n <= $reached, 'is-no' => $key === 'ditolak' && $n <= $reached])>
                        <span class="recruit-dot" aria-hidden="true">@if ($n <= $reached)<x-ikon :name="$key === 'ditolak' ? 'close' : 'check'" :size="14" />@endif</span>
                        <span class="recruit-label">{{ $label }}</span>
                        @if ($at($key))<small>{{ $at($key)->locale('id')->translatedFormat('j M Y') }}</small>@endif
                    </li>
                @endforeach
            </ol>
            @if ($c->note)<p class="recruit-note">“{{ $c->note }}”</p>@endif

            @if ($isOwner && ! $final)
                <div class="recruit-actions">
                    @if ($c->status === 'direkomendasikan')
                        <form method="POST" action="{{ route('rekrutmen.update', $internship) }}" class="inline-form grow">
                            @csrf
                            <input type="hidden" name="langkah" value="tawarkan">
                            <input type="text" name="posisi" class="input" maxlength="100" value="{{ $c->offered_position }}" placeholder="Posisi yang ditawarkan" required aria-label="Posisi yang ditawarkan">
                            <button type="submit" class="btn btn-primary"><x-ikon name="send" :size="16" /> Tawarkan kerja</button>
                        </form>
                        <form method="POST" action="{{ route('rekrutmen.update', $internship) }}" onsubmit="return confirm('Batalkan rekomendasi ini?')">
                            @csrf
                            <input type="hidden" name="langkah" value="batal">
                            <button type="submit" class="btn btn-ghost">Batalkan</button>
                        </form>
                    @else
                        <p class="hint grow">Menunggu jawaban mahasiswa. Bila dijawab di luar aplikasi, catat di sini.</p>
                        <form method="POST" action="{{ route('rekrutmen.update', $internship) }}">
                            @csrf
                            <input type="hidden" name="langkah" value="ditolak">
                            <button type="submit" class="btn">Menolak</button>
                        </form>
                        <form method="POST" action="{{ route('rekrutmen.update', $internship) }}">
                            @csrf
                            <input type="hidden" name="langkah" value="diterima">
                            <button type="submit" class="btn btn-primary">Diterima</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endif
