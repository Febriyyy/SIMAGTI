{{-- Satu kartu konfirmasi penempatan untuk Company Supervisor. Tombol di kanan, tombol utama paling kanan. --}}
@php($sid = 'sanggah-'.$internship->id)
<form method="POST" action="{{ route('supervisor.konfirmasi', $internship) }}" class="confirm-item">
    @csrf
    @method('PATCH')
    <div class="confirm-head">
        <x-avatar :user="$internship->student" />
        <div class="srow-name">
            <strong title="{{ $internship->student->name }}">{{ $internship->student->listName() }}</strong>
            <small>{{ $internship->student->nim }}@if ($code = $internship->student->studentProfile?->studyProgram?->code) · {{ $code }}@endif</small>
        </div>
    </div>

    <dl class="confirm-facts">
        <div><dt>Divisi</dt><dd>{{ $internship->division?->name ?? 'Belum diisi' }}</dd></div>
        <div><dt>Posisi</dt><dd>{{ $internship->position ?? 'Belum diisi' }}</dd></div>
        <div><dt>Mulai</dt><dd>{{ $internship->start_date->locale('id')->translatedFormat('j M Y') }}</dd></div>
        <div><dt>Selesai</dt><dd>{{ $internship->end_date->locale('id')->translatedFormat('j M Y') }}</dd></div>
    </dl>

    <input type="checkbox" id="{{ $sid }}" class="confirm-toggle">
    <div class="confirm-foot">
        <p class="hint">Sesuai data pengajuan mahasiswa?</p>
        <div class="row-actions">
            <label for="{{ $sid }}" class="btn btn-sm" role="button">Ada yang salah</label>
            <button type="submit" name="keputusan" value="benar" class="btn btn-sm btn-primary"><x-ikon name="check" :size="16" /> Benar</button>
        </div>
    </div>
    <div class="confirm-dispute">
        <div class="field">
            <label class="label" for="{{ $sid }}-catatan">Yang salah</label>
            <textarea id="{{ $sid }}-catatan" name="catatan" class="textarea" rows="2" maxlength="500" placeholder="Contoh: posisi Frontend Developer"></textarea>
        </div>
        <div class="row-actions">
            <label for="{{ $sid }}" class="btn btn-sm btn-ghost" role="button">Batal</label>
            <button type="submit" name="keputusan" value="salah" class="btn btn-sm btn-primary">Kirim ke TU</button>
        </div>
    </div>
</form>
