{{--
    Area tanda tangan seperti kertas: klik untuk memilih gambar, pratinjau langsung tampil.
    Variabel: sign (data URI|null), label (nama aksesibel)
--}}
<label @class(['cs-sign', 'has-file' => $sign])>
    <img src="{{ $sign ?? '' }}" alt="{{ $label }}" @if (! $sign) hidden @endif>
    <span class="cs-sign-empty" @if ($sign) hidden @endif>
        <x-ikon name="edit" :size="20" />
        <b>Klik untuk unggah tanda tangan</b>
    </span>
    <span class="cs-sign-line" aria-hidden="true"><i>×</i></span>
    <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" class="sr-only" data-sign-input aria-label="{{ $label }}">
</label>

@once
    <script>
        // Tampilkan tanda tangan yang baru dipilih sebelum disimpan
        document.addEventListener('change', function (e) {
            if (!e.target.matches || !e.target.matches('[data-sign-input]')) return;
            var f = e.target.files && e.target.files[0];
            var pad = e.target.closest('.cs-sign');
            if (!f || !pad) return;
            var img = pad.querySelector('img');
            img.src = URL.createObjectURL(f); img.hidden = false;
            var empty = pad.querySelector('.cs-sign-empty');
            if (empty) empty.hidden = true;
            pad.classList.add('has-file');
        });
    </script>
@endonce
