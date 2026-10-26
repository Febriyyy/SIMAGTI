/*
 * Interaksi kecil SIMAGTI tanpa library:
 * - dialog: tombol [data-open="id"] membuka <dialog>, [data-close] menutup, <dialog data-autoopen> terbuka saat halaman dimuat
 * - rencana kegiatan: tombol [data-span="7"] mengisi tanggal selesai = tanggal mulai + N hari
 * - penilaian akhir: form [data-rating-form] menghitung rata-rata tiap kategori dan perkiraan nilai akhir
 * - filter: form [data-autosubmit] terkirim saat pilihan berubah
 * - select [data-other-toggle]: pilihan "Lainnya" menampilkan isian bebas
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

        var span = e.target.closest('[data-span]');
        if (span) {
            var form = span.closest('form');
            var from = form && form.querySelector('input[name=start_date]');
            var to = form && form.querySelector('input[name=end_date]');
            if (from && to && from.value) {
                var d = new Date(from.value + 'T00:00:00');
                d.setDate(d.getDate() + parseInt(span.getAttribute('data-span'), 10) - 1);
                var max = to.getAttribute('max');
                var v = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                to.value = max && v > max ? max : v;
            }
        }
    });

    // Pilihan "Lainnya" di select [data-other-toggle="id"] menampilkan isian bebas
    document.querySelectorAll('select[data-other-toggle]').forEach(function (select) {
        var field = document.getElementById(select.getAttribute('data-other-toggle'));
        if (!field) return;
        var sync = function () {
            var other = select.value === 'lainnya';
            field.hidden = !other;
            var input = field.querySelector('input');
            if (input) { input.required = other; if (other && document.activeElement === select) input.focus(); }
        };
        select.addEventListener('change', sync);
        sync();
    });

    document.querySelectorAll('form[data-rating-form]').forEach(function (form) {
        var fmt = function (v) { return (Math.round(v * 100) / 100).toString().replace('.', ','); };
        var update = function () {
            var total = 0, any = false, answered = 0, questions = 0;
            form.querySelectorAll('fieldset[data-category]').forEach(function (group) {
                questions += group.querySelectorAll('[role=radiogroup]').length;
                var picked = Array.prototype.map.call(group.querySelectorAll('input[type=radio]:checked'), function (r) { return parseFloat(r.value); });
                var out = group.querySelector('[data-avg]');
                answered += picked.length;
                if (picked.length) {
                    var avg = picked.reduce(function (a, b) { return a + b; }, 0) / picked.length;
                    total += avg * parseFloat(group.getAttribute('data-weight') || '0') / 100;
                    any = true;
                    if (out) out.textContent = fmt(avg);
                } else if (out) { out.textContent = '–'; }
            });
            // Nilai akhir baru berarti setelah semua pertanyaan dinilai
            var final = form.querySelector('[data-final]');
            if (final) final.textContent = any && answered === questions ? fmt(total) : '–';
            var progress = form.querySelector('[data-progress]');
            if (progress) progress.textContent = answered === questions ? '' : '· ' + answered + '/' + questions + ' dinilai';
        };
        form.addEventListener('change', update);
        update();
    });

    // Bobot penilaian: total dan batang ikut berubah saat diketik
    document.querySelectorAll('form[data-weight-form]').forEach(function (form) {
        var chip = document.querySelector('[data-weight-total]');
        var update = function () {
            var total = 0;
            form.querySelectorAll('.weight-row').forEach(function (row) {
                var v = parseFloat((row.querySelector('input') || {}).value) || 0;
                total += v;
                var bar = row.querySelector('.weight-bar span');
                if (bar) bar.style.width = Math.min(100, v) + '%';
            });
            if (chip) {
                chip.textContent = (Math.round(total * 100) / 100).toString().replace('.', ',') + '%';
                chip.className = chip.className.replace(/chip-(green|red)/, Math.abs(total - 100) < 0.001 ? 'chip-green' : 'chip-red');
            }
        };
        form.addEventListener('input', update);
    });

    document.querySelectorAll('dialog[data-autoopen]').forEach(function (dlg) { if (dlg.showModal) dlg.showModal(); });

    // Tabel di HP tampil sebagai kartu: setiap sel diberi label dari kepala kolomnya (lihat kp.css, .table di max-width 600px).
    document.querySelectorAll('table.table:not(.table-keep)').forEach(function (table) {
        var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            Array.prototype.forEach.call(tr.children, function (td, i) {
                if (!td.hasAttribute('data-label')) td.setAttribute('data-label', heads[i] || '');
            });
        });
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

/* Gantt rencana kegiatan: tampilan (Gantt / Daftar), zoom (Hari / Minggu / Penuh), lipat task, geser ke hari ini.
   Pilihan pengguna diingat di localStorage (hanya kenyamanan; halaman tetap jalan tanpa itu). */
(function () {
    function load(key, fallback) { try { return localStorage.getItem(key) || fallback; } catch (e) { return fallback; } }
    function save(key, value) { try { localStorage.setItem(key, value); } catch (e) { /* abaikan */ } }

    document.querySelectorAll('[data-gantt]').forEach(function (gt) {
        var scroll = gt.querySelector('[data-gt-scroll]');
        var compact = gt.classList.contains('gt-compact');
        var days = parseInt(getComputedStyle(gt).getPropertyValue('--days'), 10) || 1;
        var collapsed = {};
        try { collapsed = JSON.parse(load('gt.collapsed', '{}')) || {}; } catch (e) { collapsed = {}; }

        function sideWidth() {
            var side = gt.querySelector('.gt-head .gt-side');
            return side ? side.getBoundingClientRect().width : 0;
        }
        function dayWidth() { return parseFloat(getComputedStyle(gt).getPropertyValue('--day')) || 16; }

        function fit() {
            if (gt.getAttribute('data-zoom') !== 'fit') return;
            var w = (scroll.clientWidth - sideWidth()) / days;
            gt.style.setProperty('--day', Math.max(2, w) + 'px');
        }

        function scrollToToday(smooth) {
            var today = scroll.getAttribute('data-today');
            if (today === null) return;
            var visible = scroll.clientWidth - sideWidth();
            var x = Number(today) * dayWidth() - visible / 3;
            scroll.scrollTo({ left: Math.max(0, x), behavior: smooth ? 'smooth' : 'auto' });
        }

        function setZoom(zoom, keep) {
            if (['day', 'week', 'fit'].indexOf(zoom) === -1) zoom = 'week';
            gt.setAttribute('data-zoom', zoom);
            gt.style.removeProperty('--day');
            gt.querySelectorAll('[data-gt-zoom]').forEach(function (b) {
                var on = b.getAttribute('data-gt-zoom') === zoom;
                b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            fit();
            if (keep) save('gt.zoom', zoom);
            scrollToToday(false);
        }

        function setView(view, keep) {
            if (view !== 'list') view = 'gantt';
            gt.setAttribute('data-view', view);
            gt.querySelectorAll('[data-gt-view]').forEach(function (b) {
                var on = b.getAttribute('data-gt-view') === view;
                b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            if (keep) save('gt.view', view);
            if (view === 'gantt') { fit(); scrollToToday(false); }
        }

        function toggle(id, open) {
            var btn = gt.querySelector('[data-gt-toggle="' + id + '"]');
            if (btn) {
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                var label = btn.getAttribute('aria-label') || '';
                btn.setAttribute('aria-label', label.replace(/^(Lipat|Buka) task/, open ? 'Lipat task' : 'Buka task'));
            }
            gt.querySelectorAll('[data-gt-parent="' + id + '"]').forEach(function (r) { r.classList.toggle('is-hidden', !open); });
        }

        gt.addEventListener('click', function (e) {
            var t = e.target.closest('[data-gt-zoom],[data-gt-view],[data-gt-toggle],[data-gt-step],[data-gt-today]');
            if (!t || !gt.contains(t)) return;
            if (t.hasAttribute('data-gt-zoom')) setZoom(t.getAttribute('data-gt-zoom'), true);
            else if (t.hasAttribute('data-gt-view')) setView(t.getAttribute('data-gt-view'), true);
            else if (t.hasAttribute('data-gt-today')) scrollToToday(true);
            else if (t.hasAttribute('data-gt-step')) scroll.scrollBy({ left: Number(t.getAttribute('data-gt-step')) * dayWidth(), behavior: 'smooth' });
            else {
                var id = t.getAttribute('data-gt-toggle');
                var open = t.getAttribute('aria-expanded') !== 'true';
                toggle(id, open);
                if (open) delete collapsed[id]; else collapsed[id] = 1;
                save('gt.collapsed', JSON.stringify(collapsed));
            }
        });

        Object.keys(collapsed).forEach(function (id) { toggle(id, false); });
        if (compact) { setZoom('fit', false); }
        else { setView(load('gt.view', 'gantt'), false); setZoom(load('gt.zoom', 'week'), false); }

        // Lebar kartu bisa berubah setelah font/ikon dimuat atau saat layar diputar: hitung ulang mode Penuh.
        var lastWidth = scroll.clientWidth;
        function refit() { if (scroll.clientWidth !== lastWidth) { lastWidth = scroll.clientWidth; fit(); } }
        if ('ResizeObserver' in window) new ResizeObserver(refit).observe(scroll);
        else window.addEventListener('resize', refit);
        window.addEventListener('load', function () { fit(); scrollToToday(false); });
    });
})();
