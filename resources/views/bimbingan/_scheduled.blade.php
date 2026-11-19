{{--
    Jadwal bimbingan yang dibuat dosen (halaman Bimbingan dosen). Satu baris per sesi:
    tanggal | topik dan keterangan | aksi. Catatan hasil dan "Tandai selesai" baru muncul setelah sesi berlangsung.
    Variabel: $sessions
--}}
<ul class="gsch">
    @foreach ($sessions as $s)
        <?php
            $days = (int) today()->diffInDays($s->held_on, false);
            $when = match (true) {
                $days < 0 => 'Sudah berlangsung',
                $days === 0 => 'Hari ini',
                $days === 1 => 'Besok',
                default => $days.' hari lagi',
            };
            $held = ! $s->held_on->isFuture();
        ?>
        <li @class(['gsch-row', 'is-due' => $held])>
            <time class="gsch-date" datetime="{{ $s->startsAt()->toIso8601String() }}">
                <b>{{ $s->held_on->format('j') }}</b>
                <span>{{ $s->held_on->locale('id')->translatedFormat('M') }}</span>
            </time>

            <div class="gsch-main min0">
                <p class="gsch-when"><span @class(['gsch-rel', 'is-now' => $days <= 0])>{{ $when }}</span> {{ $s->timeLabel() }} · {{ $s->modeLabel() }}</p>
                <h3 class="gsch-topic">{{ $s->topic }}</h3>
                <p class="gsch-who">
                    <x-avatar :user="$s->internship->student" small />
                    <span>{{ $s->internship->student->listName() }}</span>
                </p>
                @if ($s->notes)<p class="gsch-note">{{ $s->notes }}</p>@endif
            </div>

            <div class="gsch-actions">
                @if ($s->canJoin())
                    <a href="{{ $s->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary"><x-ikon name="video" :size="16" /> Gabung</a>
                @endif
                <a href="{{ \App\Support\CalendarLink::guidance($s) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-icon" title="Tambah ke Google Calendar" aria-label="Tambah {{ $s->topic }} ke Google Calendar"><x-ikon name="calendar" :size="16" /></a>
                @unless ($held)
                    <form method="POST" action="{{ route('bimbingan.decide', $s) }}" onsubmit="return confirm('Batalkan jadwal bimbingan ini? Mahasiswa akan diberi tahu.')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" name="keputusan" value="tolak" class="btn btn-sm btn-ghost gsch-cancel">Batalkan</button>
                    </form>
                @endunless
            </div>

            @if ($held)
                {{-- Setelah berlangsung: catat hasil lalu tandai selesai agar terhitung --}}
                <form method="POST" action="{{ route('bimbingan.decide', $s) }}" class="gsch-done">
                    @csrf
                    @method('PATCH')
                    <label class="sr-only" for="gsch-note-{{ $s->id }}">Catatan hasil {{ $s->topic }}</label>
                    <input id="gsch-note-{{ $s->id }}" type="text" name="catatan" class="input" maxlength="500" placeholder="Catatan hasil bimbingan (opsional)">
                    <button type="submit" name="keputusan" value="tolak" class="btn btn-sm btn-ghost gsch-cancel" onclick="return confirm('Batalkan jadwal bimbingan ini?')">Batalkan</button>
                    <button type="submit" name="keputusan" value="konfirmasi" class="btn btn-sm btn-primary"><x-ikon name="check" :size="16" /> Tandai selesai</button>
                </form>
            @endif
        </li>
    @endforeach
</ul>
