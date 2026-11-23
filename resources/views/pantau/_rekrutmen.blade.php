{{--
    Rekomendasi jadi karyawan di halaman detail mahasiswa. Company Supervisor mengelola langkahnya
    (kirim tawaran resmi, perpanjang batas); dosen pembimbing dan kaprodi hanya melihat status dan posisinya.
--}}
<?php
    $c = $internship->conversion;
    $isOwner = auth()->user()->role === 'supervisor' && $internship->isSupervisedBy(auth()->user());
    $at = fn (string $status) => $c?->histories->where('to_status', $status)->sortByDesc('created_at')->first()?->created_at;
    $last = match ($c?->status) {
        'ditolak' => ['ditolak', 'Menolak', 'close'],
        'kedaluwarsa' => ['kedaluwarsa', 'Kedaluwarsa', 'clock'],
        default => ['diterima', 'Diterima', 'tick'],
    };
    $steps = [['direkomendasikan', 'Direkomendasikan', 'tick'], ['ditawari', 'Ditawari kerja', 'tick'], $last];
    $order = ['direkomendasikan' => 0, 'ditawari' => 1, 'diterima' => 2, 'ditolak' => 2, 'kedaluwarsa' => 2];
    $reached = $c ? $order[$c->status] : -1;
    $left = $c?->daysLeft();
?>
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
            {{-- Garis penuh dari titik pertama ke titik terakhir; bagian hijau sesuai tahap yang dicapai --}}
            <ol class="recruit-steps" style="--fill: {{ max(0, $reached) / 2 }}">
                @foreach ($steps as $n => [$key, $label, $icon])
                    <li @class(['is-done' => $n <= $reached, 'is-no' => in_array($key, ['ditolak', 'kedaluwarsa'], true) && $n <= $reached])>
                        <span class="recruit-dot" aria-hidden="true">@if ($n <= $reached)<x-ikon :name="$icon" :size="14" />@endif</span>
                        <span class="recruit-label">{{ $label }}</span>
                        @if ($at($key))<small>{{ $at($key)->locale('id')->translatedFormat('j M Y') }}</small>@endif
                    </li>
                @endforeach
            </ol>

            @if ($c->offered_at)
                {{-- Ringkasan tawaran: detail lengkap hanya untuk supervisor; dosen dan kaprodi cukup posisi dan status --}}
                <dl class="recruit-facts">
                    <div><dt>Posisi</dt><dd>{{ $c->offered_position }}@if ($c->unit)<small>{{ $c->unit }}</small>@endif</dd></div>
                    @if ($isOwner)
                        <div><dt>Penempatan</dt><dd>{{ $c->work_location }}<small>{{ $c->workModeLabel() }}</small></dd></div>
                        <div><dt>Hubungan kerja</dt><dd>{{ $c->employmentLabel() }}@if ($c->start_date)<small>Mulai {{ $c->start_date->locale('id')->translatedFormat('j M Y') }}</small>@endif</dd></div>
                    @endif
                    <div><dt>{{ $c->status === 'ditawari' ? 'Batas jawaban' : 'Dijawab' }}</dt>
                        <dd>
                            @if ($c->status === 'ditawari')
                                {{ $c->respond_by?->locale('id')->translatedFormat('j M Y') }}<small>{{ $left === 0 ? 'Hari ini terakhir' : $left.' hari lagi' }}</small>
                            @elseif ($c->responded_at)
                                {{ $c->responded_at->locale('id')->translatedFormat('j M Y') }}
                            @else
                                Tidak dijawab<small>Batas {{ $c->respond_by?->locale('id')->translatedFormat('j M Y') }}</small>
                            @endif
                        </dd>
                    </div>
                </dl>
            @endif

            @if ($c->status === 'ditolak' && $c->decline_reason)
                <p class="recruit-note"><b>Alasan:</b> {{ $c->declineReasonLabel() }}@if ($c->decline_note) · “{{ $c->decline_note }}”@endif</p>
            @elseif ($c->status === 'direkomendasikan' && $c->note)
                <p class="recruit-note">“{{ $c->note }}”</p>
            @endif

            @if ($isOwner && in_array($c->status, ['direkomendasikan', 'ditawari', 'kedaluwarsa'], true))
                <div class="recruit-actions">
                    @if ($c->status === 'direkomendasikan')
                        <form method="POST" action="{{ route('rekrutmen.update', $internship) }}" onsubmit="return confirm('Batalkan rekomendasi ini?')">
                            @csrf
                            <input type="hidden" name="langkah" value="batal">
                            <button type="submit" class="btn btn-ghost">Batalkan</button>
                        </form>
                        <button type="button" class="btn btn-primary" data-open="dlg-offer"><x-ikon name="send" :size="16" /> Kirim tawaran kerja</button>
                    @else
                        @if ($c->offerLetter)
                            <a href="{{ route('rekrutmen.surat', $c) }}" target="_blank" rel="noopener" class="btn btn-ghost"><x-ikon name="file" :size="16" /> Lihat surat</a>
                        @endif
                        <button type="button" class="btn" data-open="dlg-extend"><x-ikon name="calendar" :size="16" /> Perpanjang batas</button>
                    @endif
                </div>
            @endif
        </div>
    </section>

    @if ($isOwner && $c->status === 'direkomendasikan')
        @php($e = $errors->offer)
        <dialog id="dlg-offer" class="dialog dialog-wide" @if ($e->any()) data-autoopen @endif>
            <form method="POST" action="{{ route('rekrutmen.tawarkan', $internship) }}" enctype="multipart/form-data" class="dialog-body">
                @csrf
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Kirim tawaran kerja</h2>
                        <p class="card-sub">Untuk {{ $internship->student->name }} · dibaca mahasiswa sebelum memutuskan</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>

                <div class="offer-form">
                    <fieldset class="offer-fs">
                        <legend>Pekerjaan</legend>
                        <div class="form-grid">
                            <div>
                                <label class="label" for="of-pos">Posisi</label>
                                <input id="of-pos" type="text" name="posisi" class="input" maxlength="100" required value="{{ old('posisi', $c->offered_position) }}">
                                @if ($e->has('posisi'))<p class="field-error">{{ $e->first('posisi') }}</p>@endif
                            </div>
                            <div>
                                <label class="label" for="of-unit">Divisi atau tim <span class="muted">(opsional)</span></label>
                                <input id="of-unit" type="text" name="unit" class="input" maxlength="100" value="{{ old('unit', $internship->division?->name) }}">
                            </div>
                            <div>
                                <label class="label" for="of-jenis">Hubungan kerja</label>
                                <select id="of-jenis" name="jenis" class="select" required>
                                    @foreach (\App\Models\ConversionCandidate::EMPLOYMENT_TYPES as $k => $label)
                                        <option value="{{ $k }}" @selected(old('jenis', 'kontrak') === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="of-dur">Lama kontrak (bulan) <span class="muted">(bila kontrak)</span></label>
                                <input id="of-dur" type="number" name="durasi" class="input" min="1" max="60" value="{{ old('durasi', 12) }}">
                                @if ($e->has('durasi'))<p class="field-error">{{ $e->first('durasi') }}</p>@endif
                            </div>
                            <div class="span-all">
                                <label class="label" for="of-desc">Deskripsi pekerjaan</label>
                                <textarea id="of-desc" name="deskripsi" class="textarea" rows="4" maxlength="2000" required placeholder="Satu baris satu tanggung jawab, mis.&#10;Mengembangkan dan memelihara API layanan pemesanan&#10;Menulis pengujian otomatis">{{ old('deskripsi') }}</textarea>
                                @if ($e->has('deskripsi'))<p class="field-error">{{ $e->first('deskripsi') }}</p>@endif
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="offer-fs">
                        <legend>Penempatan</legend>
                        <div class="form-grid">
                            <div>
                                <label class="label" for="of-loc">Lokasi kerja</label>
                                <input id="of-loc" type="text" name="lokasi" class="input" maxlength="150" required value="{{ old('lokasi', $internship->company?->city) }}">
                                @if ($e->has('lokasi'))<p class="field-error">{{ $e->first('lokasi') }}</p>@endif
                            </div>
                            <div>
                                <label class="label" for="of-mode">Cara kerja</label>
                                <select id="of-mode" name="mode" class="select" required>
                                    @foreach (\App\Models\ConversionCandidate::WORK_MODES as $k => $label)
                                        <option value="{{ $k }}" @selected(old('mode', 'wfo') === $k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label" for="of-start">Perkiraan mulai <span class="muted">(opsional)</span></label>
                                <input id="of-start" type="date" name="mulai" class="input" min="{{ today()->toDateString() }}" value="{{ old('mulai') }}">
                                @if ($e->has('mulai'))<p class="field-error">{{ $e->first('mulai') }}</p>@endif
                            </div>
                            <div>
                                <label class="label" for="of-pay">Kompensasi <span class="muted">(opsional)</span></label>
                                <input id="of-pay" type="text" name="kompensasi" class="input" maxlength="150" placeholder="Dibahas saat wawancara" value="{{ old('kompensasi') }}">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="offer-fs">
                        <legend>Kontak dan surat resmi</legend>
                        <div class="form-grid">
                            <div>
                                <label class="label" for="of-hr">Nama kontak HR</label>
                                <input id="of-hr" type="text" name="hr_nama" class="input" maxlength="100" required value="{{ old('hr_nama', auth()->user()->name) }}">
                                @if ($e->has('hr_nama'))<p class="field-error">{{ $e->first('hr_nama') }}</p>@endif
                            </div>
                            <div>
                                <label class="label" for="of-hrmail">Email kontak HR</label>
                                <input id="of-hrmail" type="email" name="hr_email" class="input" maxlength="150" required value="{{ old('hr_email', auth()->user()->email) }}">
                                @if ($e->has('hr_email'))<p class="field-error">{{ $e->first('hr_email') }}</p>@endif
                            </div>
                            <div>
                                <label class="label" for="of-letter">Surat penawaran kerja (PDF, maks. 5 MB)</label>
                                <input id="of-letter" type="file" name="surat" class="input" accept=".pdf,application/pdf" required>
                                @if ($e->has('surat'))<p class="field-error">{{ $e->first('surat') }}</p>@endif
                            </div>
                            <div>
                                <label class="label" for="of-due">Batas jawaban</label>
                                <input id="of-due" type="date" name="batas" class="input" required min="{{ today()->addDay()->toDateString() }}" max="{{ today()->addDays(60)->toDateString() }}" value="{{ old('batas', today()->addDays(\App\Models\ConversionCandidate::DEFAULT_RESPOND_DAYS)->toDateString()) }}">
                                @if ($e->has('batas'))<p class="field-error">{{ $e->first('batas') }}</p>@endif
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary"><x-ikon name="send" :size="16" /> Kirim tawaran</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($isOwner && in_array($c->status, ['ditawari', 'kedaluwarsa'], true))
        @php($x = $errors->extend)
        <dialog id="dlg-extend" class="dialog" @if ($x->any()) data-autoopen @endif>
            <form method="POST" action="{{ route('rekrutmen.perpanjang', $internship) }}" class="dialog-body">
                @csrf
                @method('PATCH')
                <div class="dialog-head">
                    <div>
                        <h2 class="card-title">Perpanjang batas jawaban</h2>
                        <p class="card-sub">Mahasiswa diberi tahu tanggal barunya</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                </div>
                <div>
                    <label class="label" for="ex-due">Batas jawaban baru</label>
                    <input id="ex-due" type="date" name="batas" class="input" required min="{{ today()->addDay()->toDateString() }}" max="{{ today()->addDays(60)->toDateString() }}" value="{{ old('batas', today()->addDays(\App\Models\ConversionCandidate::DEFAULT_RESPOND_DAYS)->toDateString()) }}">
                    @if ($x->has('batas'))<p class="field-error">{{ $x->first('batas') }}</p>@endif
                </div>
                <div class="dialog-foot row-actions">
                    <button type="button" class="btn" data-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </dialog>
    @endif
@endif
