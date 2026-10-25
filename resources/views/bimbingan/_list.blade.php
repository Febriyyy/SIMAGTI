{{-- Daftar sesi bimbingan. $sessions, $canDelete (mahasiswa), $canDecide (dosen pembimbing) --}}
@if ($sessions->isEmpty())
    <div class="empty"><strong>Belum ada bimbingan</strong> Catat setiap sesi bimbingan dengan dosen pembimbing.</div>
@else
    <ul class="item-list">
        @foreach ($sessions as $s)
            <li class="item-row guidance">
                <span class="item-icon tone-bg-{{ \App\Models\GuidanceSession::STATUSES[$s->status][1] ?? 'blue' }}"><x-ikon name="chat" :size="18" /></span>
                <div class="grow min0">
                    <div class="cell-title" title="{{ $s->topic }}">{{ \Illuminate\Support\Str::limit($s->topic, 40) }}</div>
                    <div class="cell-sub">{{ $s->held_on->locale('id')->translatedFormat('j M Y') }}@isset($showStudent) · {{ $s->internship->student->listName() }}@endisset</div>
                </div>
                <div class="guidance-actions">
                    <x-chip :tone="\App\Models\GuidanceSession::STATUSES[$s->status][1] ?? 'gray'">{{ \App\Models\GuidanceSession::STATUSES[$s->status][0] ?? $s->status }}</x-chip>
                    @if (($canDelete ?? false) && $s->status !== 'confirmed')
                        <form method="POST" action="{{ route('bimbingan.destroy', $s) }}" onsubmit="return confirm('Hapus catatan bimbingan ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost" aria-label="Hapus"><x-ikon name="delete" :size="16" /></button>
                        </form>
                    @endif
                </div>
                <x-detail>
                    <dl>
                        @isset($showStudent)<div><dt>Mahasiswa</dt><dd>{{ $s->internship->student->name }}</dd></div>@endisset
                        <div><dt>Topik</dt><dd>{{ $s->topic }}</dd></div>
                        <div><dt>Waktu</dt><dd>{{ $s->held_on->locale('id')->translatedFormat('l, j F Y') }} · {{ \App\Models\GuidanceSession::MODES[$s->mode] ?? $s->mode }}</dd></div>
                        @if ($s->notes)<div><dt>Catatan mahasiswa</dt><dd>{{ $s->notes }}</dd></div>@endif
                        @if ($s->lecturer_note)<div><dt>Catatan dosen</dt><dd>{{ $s->lecturer_note }}</dd></div>@endif
                    </dl>
                </x-detail>
                @if (($canDecide ?? false) && $s->status === 'pending')
                    <form method="POST" action="{{ route('bimbingan.decide', $s) }}" class="inline-form guidance-decide">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="catatan" class="input input-sm" maxlength="500" placeholder="Catatan (opsional)" aria-label="Catatan untuk {{ $s->topic }}">
                        <button type="submit" name="keputusan" value="tolak" class="btn btn-sm">Tolak</button>
                        <button type="submit" name="keputusan" value="konfirmasi" class="btn btn-sm btn-primary"><x-ikon name="check" :size="16" /> Konfirmasi</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
@endif
