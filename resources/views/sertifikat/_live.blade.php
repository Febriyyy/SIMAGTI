{{--
    Pratinjau langsung di lembar sertifikat sebelum disimpan:
    - isian data-live-name="k" mengisi elemen data-live-name-out="k"
    - berkas data-live-sign="k" menampilkan gambar di data-live-sign-out="k" dan menyembunyikan data-live-sign-empty="k"
--}}
@once
    <script>
        document.addEventListener('input', function (e) {
            var k = e.target.getAttribute && e.target.getAttribute('data-live-name');
            if (!k) return;
            document.querySelectorAll('[data-live-name-out="' + k + '"]').forEach(function (el) {
                var v = e.target.value.trim();
                el.textContent = v || el.getAttribute('data-empty');
                el.classList.toggle('is-empty', !v);
            });
        });
        document.addEventListener('change', function (e) {
            var k = e.target.getAttribute && e.target.getAttribute('data-live-sign');
            var f = e.target.files && e.target.files[0];
            if (!k || !f) return;
            var url = URL.createObjectURL(f);
            document.querySelectorAll('[data-live-sign-out="' + k + '"]').forEach(function (img) { img.src = url; img.hidden = false; });
            document.querySelectorAll('[data-live-sign-empty="' + k + '"]').forEach(function (el) { el.hidden = true; });
        });
    </script>
@endonce
