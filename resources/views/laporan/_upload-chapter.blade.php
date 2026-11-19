{{-- Tombol unggah berkas satu bab (mahasiswa); setelah unggah kembali ke halaman Laporan KP. Variabel: chapter, label, primary --}}
<form method="POST" action="{{ route('laporan.unggah', $chapter) }}" enctype="multipart/form-data" class="rd-up">
    @csrf
    <input type="hidden" name="kembali" value="laporan">
    <label @class(['btn', 'btn-sm', 'btn-primary' => $primary])>
        <x-ikon name="upload" :size="16" /> {{ $label }}
        <input type="file" name="berkas" accept=".pdf,.docx" required class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="{{ $label }}: {{ $chapter->label() }}">
    </label>
</form>
