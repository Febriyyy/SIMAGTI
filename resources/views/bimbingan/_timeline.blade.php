{{--
    Riwayat bimbingan mahasiswa sebagai linimasa (terbaru di atas): tanggal di kiri, titik status di garis, isi di kanan.
    Variabel: $sessions, $canDelete
--}}
@if ($sessions->isEmpty())
    <div class="empty"><strong>Belum ada bimbingan</strong> Catat setiap sesi bimbingan dengan dosen pembimbing setelah selesai.</div>
@else
    <ol class="gtl">
        @foreach ($sessions as $s)
            @php($tone = $s->statusTone())
            <li class="gtl-item is-{{ $s->status }}">
                <time class="gtl-date" datetime="{{ $s->held_on->toDateString() }}">
                    <b>{{ $s->held_on->format('j') }}</b>
                    <span>{{ $s->held_on->locale('id')->translatedFormat('M Y') }}</span>
                </time>
                <span class="gtl-dot tone-{{ $tone }}" aria-hidden="true">
                    @if ($s->status === 'confirmed')<x-ikon name="tick" :size="12" />@endif
                </span>
                <div class="gtl-body">
                    <div class="gtl-top">
                        <h3 class="gtl-title">{{ $s->topic }}</h3>
                        @if ($s->canJoin())
                            <a href="{{ $s->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary"><x-ikon name="video" :size="16" /> Gabung</a>
                        @elseif (($canDelete ?? false) && in_array($s->status, ['pending', 'rejected'], true))
                            <form method="POST" action="{{ route('bimbingan.destroy', $s) }}" onsubmit="return confirm('Hapus catatan bimbingan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-ghost" aria-label="Hapus bimbingan {{ $s->topic }}" title="Hapus"><x-ikon name="delete" :size="16" /></button>
                            </form>
                        @endif
                    </div>
                    <div class="gtl-meta">
                        <x-chip :tone="$tone">{{ $s->statusLabel() }}</x-chip>
                        <span>{{ $s->timeLabel() ? $s->timeLabel().' · ' : '' }}{{ $s->modeLabel() }}</span>
                        @if ($s->status === 'scheduled')<span>Dijadwalkan dosen</span>@endif
                    </div>
                    @if ($s->lecturer_note)
                        <p class="gtl-note"><strong>Catatan dosen:</strong> {{ $s->lecturer_note }}</p>
                    @endif
                    @if ($s->notes && $s->status === 'scheduled')
                        <p class="gtl-note"><strong>Pesan dosen:</strong> {{ $s->notes }}</p>
                    @elseif ($s->notes)
                        <x-detail>
                            <dl>
                                <div><dt>Catatan Anda</dt><dd>{{ $s->notes }}</dd></div>
                                <div><dt>Waktu</dt><dd>{{ $s->held_on->locale('id')->translatedFormat('l, j F Y') }} · {{ $s->modeLabel() }}</dd></div>
                            </dl>
                        </x-detail>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
