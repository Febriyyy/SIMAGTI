{{-- Input lampiran materi task. Dipakai di form tambah task dan di halaman detail task. --}}
<span class="label">Lampiran materi (opsional)</span>
<label class="dropzone">
    <span class="dropzone-icon"><x-icon name="paperclip" :size="20" /></span>
    <span class="dropzone-text">
        <strong data-dropzone-title>Pilih berkas dari komputer</strong>
        <span data-dropzone-files>PDF, PowerPoint (PPT/PPTX), atau gambar (JPG/PNG/WEBP)</span>
        <small>Maks. {{ \App\Models\TaskAttachment::MAX_FILES }} berkas, masing-masing {{ \App\Models\TaskAttachment::MAX_KB / 1024 }} MB</small>
    </span>
    <span class="btn btn-sm dropzone-btn">Telusuri</span>
    <input name="lampiran[]" type="file" multiple class="dropzone-input"
           accept=".pdf,.ppt,.pptx,.jpg,.jpeg,.png,.webp,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,image/jpeg,image/png,image/webp"
           onchange="(function (input) {
               var box = input.closest('.dropzone'), names = Array.prototype.map.call(input.files, function (f) { return f.name; });
               box.querySelector('[data-dropzone-title]').textContent = names.length ? names.length + ' berkas dipilih' : 'Pilih berkas dari komputer';
               box.querySelector('[data-dropzone-files]').textContent = names.length ? names.join(', ') : 'PDF, PowerPoint (PPT/PPTX), atau gambar (JPG/PNG/WEBP)';
               box.classList.toggle('has-files', names.length > 0);
           })(this)">
</label>
