{{--
    Kotak pilih berkas (bisa banyak). Dipakai untuk bukti pengerjaan task dari mahasiswa.
    Variabel opsional: $upName, $upLabel, $upHint, $upAccept, $upMax, $upMb
--}}
@php
    $upName ??= 'lampiran';
    $upLabel ??= 'Lampiran materi (opsional)';
    $upHint ??= 'PDF, PowerPoint (PPT/PPTX), atau gambar (JPG/PNG/WEBP)';
    $upAccept ??= '.pdf,.ppt,.pptx,.jpg,.jpeg,.png,.webp';
    $upMax ??= \App\Models\TaskAttachment::MAX_FILES;
    $upMb ??= \App\Models\TaskAttachment::MAX_KB / 1024;
@endphp
<span class="label">{{ $upLabel }}</span>
<label class="dropzone">
    <span class="dropzone-icon"><x-ikon name="upload" :size="20" /></span>
    <span class="dropzone-text">
        <strong data-dropzone-title>Pilih berkas dari komputer</strong>
        <span data-dropzone-files data-default="{{ $upHint }}">{{ $upHint }}</span>
        <small>Maks. {{ $upMax }} berkas, masing-masing {{ $upMb }} MB</small>
    </span>
    <span class="btn btn-sm dropzone-btn">Telusuri</span>
    <input name="{{ $upName }}[]" type="file" multiple class="dropzone-input" accept="{{ $upAccept }}"
           onchange="(function (input) {
               var box = input.closest('.dropzone'), list = box.querySelector('[data-dropzone-files]'),
                   names = Array.prototype.map.call(input.files, function (f) { return f.name; });
               box.querySelector('[data-dropzone-title]').textContent = names.length ? names.length + ' berkas dipilih' : 'Pilih berkas dari komputer';
               list.textContent = names.length ? names.join(', ') : list.getAttribute('data-default');
               box.classList.toggle('has-files', names.length > 0);
           })(this)">
</label>
