/*
 * Interaksi kecil SIMAGTI tanpa library:
 * - dialog: tombol [data-open="id"] membuka <dialog>, [data-close] menutup
 * - filter: form [data-autosubmit] terkirim saat pilihan berubah
 * - form pengajuan: pilihan jalur menampilkan isian yang sesuai
 * - board: drag-and-drop kartu antar kolom (PATCH ke server, kembali ke posisi semula bila gagal)
 */
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;

    document.addEventListener('click', function (e) {
        var open = e.target.closest('[data-open]');
        if (open) {
            var dlg = document.getElementById(open.getAttribute('data-open'));
            if (dlg && dlg.showModal) { dlg.showModal(); var f = dlg.querySelector('input:not([type=hidden]),select,textarea'); if (f) f.focus(); }
        }
        var close = e.target.closest('[data-close]');
        if (close) { var d = close.closest('dialog'); if (d) d.close(); }
        if (e.target.tagName === 'DIALOG') e.target.close(); // klik di luar isi dialog
    });

    document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
        var timer;
        form.addEventListener('change', function () { form.submit(); });
        form.querySelectorAll('input[type=search]').forEach(function (input) {
            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { form.submit(); }, 600);
            });
        });
    });

    // ---------- Board ----------
    var board = document.querySelector('[data-board]');
    if (!board) return;

    var dragged = null, origin = null, originNext = null;

    function refresh(col) {
        var body = col.querySelector('[data-drop]');
        var cards = body.querySelectorAll('.kcard');
        col.querySelector('[data-count]').textContent = cards.length;
        var empty = body.querySelector('.kcol-empty');
        if (empty) empty.hidden = cards.length > 0;
    }

    function afterElement(container, y) {
        var cards = Array.prototype.slice.call(container.querySelectorAll('.kcard:not(.is-dragging)'));
        var closest = null, offset = -Infinity;
        cards.forEach(function (card) {
            var box = card.getBoundingClientRect();
            var d = y - box.top - box.height / 2;
            if (d < 0 && d > offset) { offset = d; closest = card; }
        });
        return closest;
    }

    board.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.kcard[draggable="true"]');
        if (!card) return;
        dragged = card; origin = card.parentNode; originNext = card.nextSibling;
        card.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.getAttribute('data-task'));
    });

    board.addEventListener('dragover', function (e) {
        if (!dragged) return;
        var body = e.target.closest('[data-drop]');
        if (!body) return;
        e.preventDefault();
        board.querySelectorAll('.kcol.is-over').forEach(function (c) { c.classList.remove('is-over'); });
        body.closest('.kcol').classList.add('is-over');
        var after = afterElement(body, e.clientY);
        var empty = body.querySelector('.kcol-empty');
        if (after) body.insertBefore(dragged, after); else body.insertBefore(dragged, empty || null);
    });

    board.addEventListener('dragend', function () {
        if (!dragged) return;
        var card = dragged; dragged = null;
        card.classList.remove('is-dragging');
        board.querySelectorAll('.kcol.is-over').forEach(function (c) { c.classList.remove('is-over'); });

        var col = card.closest('.kcol');
        var fromCol = origin.closest('.kcol');
        var index = Array.prototype.indexOf.call(col.querySelectorAll('.kcard'), card);
        refresh(col); if (fromCol !== col) refresh(fromCol);

        fetch(card.getAttribute('data-move-url'), {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ board_column_id: Number(col.getAttribute('data-column')), position: index })
        }).then(function (res) {
            if (!res.ok) throw new Error('gagal');
            return res.json();
        }).then(function (data) {
            card.classList.toggle('is-done', data.status === 'completed');
        }).catch(function () {
            origin.insertBefore(card, originNext);
            refresh(col); refresh(fromCol);
            alert('Task gagal dipindahkan. Coba muat ulang halaman.');
        });
    });
})();
