{{--
    Jadwal bimbingan yang dibuat dosen (halaman Bimbingan dosen). Satu baris per sesi:
    tanggal | topik, lalu mahasiswa dan cara (berikon, boleh turun baris) | jam + waktu relatif | aksi.
    Tata letak mengikuti lebar kartu (container query), bukan lebar layar, jadi tidak terpotong di kolom sempit.
    Setelah sesi berlangsung, "Tandai selesai" membuka dialog untuk catatan hasil.
    Variabel: $sessions
--}}
<ul class="gsch">
    @foreach ($sessions as $s)
        <?php
            $days = (int) today()->diffInDays($s->held_on, false);
            $when = match (true) {
                $days < 0 => 'Sudah lewat',
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
                <h3 class="gsch-topic" title="{{ $s->topic }}">{{ $s->topic }}</h3>
                <p class="gsch-meta" @if ($s->notes) title="Pesan: {{ $s->notes }}" @endif>
                    <span><x-ikon name="person" :size="15" />{{ $s->internship->student->listName() }}</span>
                    <span><x-ikon name="{{ $s->mode === 'daring' ? 'video' : 'building' }}" :size="15" />{{ $s->modeLabel() }}</span>
                </p>
            </div>

            <p @class(['gsch-time', 'is-now' => $held])>
                <b>{{ $s->start_time ? substr($s->start_time, 0, 2).'.'.substr($s->start_time, 3, 2) : '–' }}</b>
                <span>{{ $when }}</span>
            </p>

            <div class="gsch-actions">
                @if ($held)
                    <button type="button" class="btn btn-sm btn-primary" data-open="dlg-gdone-{{ $s->id }}"><x-ikon name="check" :size="16" /> <span class="gsch-long">Tandai selesai</span><span class="gsch-short">Selesai</span></button>
                @elseif ($s->canJoin())
                    <a href="{{ $s->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary"><x-ikon name="video" :size="16" /> Gabung</a>
                @endif
                <a href="{{ \App\Support\CalendarLink::guidance($s) }}" target="_blank" rel="noopener noreferrer" class="gsch-icon" title="Tambah ke Google Calendar" aria-label="Tambah {{ $s->topic }} ke Google Calendar"><x-ikon name="calendar" :size="18" /></a>
                <form method="POST" action="{{ route('bimbingan.decide', $s) }}" onsubmit="return confirm('Batalkan jadwal bimbingan ini? Mahasiswa akan diberi tahu.')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" name="keputusan" value="tolak" class="gsch-icon is-danger" title="Batalkan jadwal" aria-label="Batalkan jadwal {{ $s->topic }}"><x-ikon name="close" :size="18" /></button>
                </form>
            </div>
        </li>
    @endforeach
</ul>

{{-- Catatan hasil bimbingan sebelum ditandai selesai --}}
@foreach ($sessions->filter(fn ($s) => ! $s->held_on->isFuture()) as $s)
    <dialog id="dlg-gdone-{{ $s->id }}" class="dialog">
        <form method="POST" action="{{ route('bimbingan.decide', $s) }}" class="dialog-body">
            @csrf
            @method('PATCH')
            <div class="dialog-head">
                <div>
                    <h2 class="card-title">Tandai bimbingan selesai</h2>
                    <p class="card-sub">{{ $s->topic }} · {{ $s->internship->student->name }}</p>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
            </div>
            @if ($s->notes)
                <p class="gsch-msg"><b>Pesan Anda</b> {{ $s->notes }}</p>
            @endif
            <div class="field">
                <label class="label" for="gdone-note-{{ $s->id }}">Catatan hasil <span class="label-opt">(opsional)</span></label>
                <textarea id="gdone-note-{{ $s->id }}" name="catatan" class="textarea" rows="3" maxlength="500" placeholder="Contoh: Bab 3 sudah sesuai, lanjut ke bab 4."></textarea>
            </div>
            <p class="hint">Sesi yang selesai terhitung untuk syarat minimal bimbingan mahasiswa.</p>
            <div class="dialog-foot row-actions">
                <button type="button" class="btn" data-close>Batal</button>
                <button type="submit" name="keputusan" value="konfirmasi" class="btn btn-primary"><x-ikon name="check" :size="16" /> Tandai selesai</button>
            </div>
        </form>
    </dialog>
@endforeach
