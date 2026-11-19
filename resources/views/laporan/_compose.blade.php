{{-- Tulis komentar pada satu bab (chapterId) atau laporan akhir (chapterId null). Dosen bisa melampirkan berkas koreksi. --}}
<?php $fid = 'rd-'.($chapterId ?? 'akhir'); ?>
<form method="POST" action="{{ route('laporan.komentar', $internship) }}" enctype="multipart/form-data" class="rd-compose" data-rd-compose>
    @csrf
    @if ($chapterId)<input type="hidden" name="bab" value="{{ $chapterId }}">@endif
    <label for="{{ $fid }}" class="sr-only">Komentar untuk {{ $label }}</label>
    <textarea id="{{ $fid }}" name="isi" class="textarea" rows="2" maxlength="2000" placeholder="{{ $isLecturer ? 'Tulis masukan untuk '.$label.'…' : 'Tulis pertanyaan atau tanggapan…' }}"></textarea>
    <div class="rd-compose-foot">
        <label class="rd-pick">
            <x-ikon name="paperclip" :size="16" />
            <span data-rd-name>{{ $isLecturer ? 'Lampirkan berkas koreksi' : 'Lampirkan berkas' }}</span>
            <input type="file" name="berkas" accept=".pdf,.docx" class="sr-only" onchange="this.closest('[data-rd-compose]').querySelector('[data-rd-name]').textContent = this.files[0] ? this.files[0].name : this.dataset.empty" data-empty="{{ $isLecturer ? 'Lampirkan berkas koreksi' : 'Lampirkan berkas' }}">
        </label>
        <button type="submit" class="btn btn-sm btn-primary"><x-ikon name="send" :size="16" /> Kirim</button>
    </div>
</form>
