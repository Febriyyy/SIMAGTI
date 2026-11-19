{{--
    Jadwal bimbingan dari dosen sebagai kartu tiket janji temu:
    potongan kiri (hari, tanggal, jam) | garis sobekan | isi tiket (waktu relatif, cara, topik, mahasiswa) | aksi.
    Tata letak mengikuti lebar kartu (container query). "Tandai selesai" membuka dialog catatan hasil.
    Variabel: $sessions
--}}
<ul class="gtk-list">
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
            $time = $s->start_time ? substr($s->start_time, 0, 2).'.'.substr($s->start_time, 3, 2) : null;
        ?>
        <li @class(['gtk', 'is-due' => $held])>
            <time class="gtk-stub" datetime="{{ $s->startsAt()->toIso8601String() }}">
                <span class="gtk-dow">{{ $s->held_on->locale('id')->translatedFormat('D') }}</span>
                <b class="gtk-day">{{ $s->held_on->format('j') }}</b>
                <span class="gtk-mon">{{ $s->held_on->locale('id')->translatedFormat('M') }}</span>
                @if ($time)<span class="gtk-clock">{{ $time }}</span>@endif
            </time>

            <div class="gtk-body min0">
                <p class="gtk-tags">
                    <span @class(['gtk-rel', 'is-now' => $held])>{{ $when }}</span>
                    <span class="gtk-mode"><x-ikon name="{{ $s->mode === 'daring' ? 'video' : 'building' }}" :size="14" />{{ $s->modeLabel() }}</span>
                </p>
                <h3 class="gtk-topic">{{ $s->topic }}</h3>
                <p class="gtk-who" @if ($s->notes) title="Pesan: {{ $s->notes }}" @endif>
                    <x-avatar :user="$s->internship->student" small />
                    <span>{{ $s->internship->student->listName() }}</span>
                </p>
            </div>

            <div class="gtk-act">
                @if ($held)
                    <button type="button" class="btn btn-sm btn-primary" data-open="dlg-gdone-{{ $s->id }}"><x-ikon name="check" :size="16" /> Tandai selesai</button>
                @elseif ($s->canJoin())
                    <a href="{{ $s->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary"><x-ikon name="video" :size="16" /> Gabung</a>
                @endif
                <span class="gtk-tools">
                    <a href="{{ \App\Support\CalendarLink::guidance($s) }}" target="_blank" rel="noopener noreferrer" class="gtk-icon" title="Tambah ke Google Calendar" aria-label="Tambah {{ $s->topic }} ke Google Calendar"><x-ikon name="calendar" :size="18" /></a>
                    <form method="POST" action="{{ route('bimbingan.decide', $s) }}" onsubmit="return confirm('Batalkan jadwal bimbingan ini? Mahasiswa akan diberi tahu.')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" name="keputusan" value="tolak" class="gtk-icon is-danger" title="Batalkan jadwal" aria-label="Batalkan jadwal {{ $s->topic }}"><x-ikon name="close" :size="18" /></button>
                    </form>
                </span>
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
