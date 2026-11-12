/*
 * Interaksi kecil SIMAGTI tanpa library:
 * - dialog: tombol [data-open="id"] membuka <dialog>, [data-close] menutup, <dialog data-autoopen> terbuka saat halaman dimuat
 * - rencana kegiatan: tombol [data-span="7"] mengisi tanggal selesai = tanggal mulai + N hari
 * - penilaian akhir: form [data-rating-form] menghitung rata-rata tiap kategori dan perkiraan nilai akhir
 * - filter: form [data-autosubmit] terkirim saat pilihan berubah; pilihan di pop-up filter menunggu tombol Terapkan
 * - select [data-other-toggle]: pilihan "Lainnya" menampilkan isian bebas
 * - board: drag-and-drop kartu antar kolom (PATCH ke server, kembali ke posisi semula bila gagal)
 * - menu HP: tombol [data-drawer-open] membuka sidebar sebagai drawer, [data-drawer-close] / Esc menutupnya
 * - sedikit kata: subjudul dan penjelasan panjang dipindah ke ikon info (tooltip)
 * - perayaan: konfeti sekali untuk [data-celebrate] dan saat goal tercapai
 */
(function () {
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (window.CSS && CSS.registerProperty) document.documentElement.classList.add('ring-anim');

    // ---------- Tab di dalam halaman ([data-tabs] + [data-tab-panel]); #catatan / ?p / ?periode membuka tab Catatan ----------
    document.querySelectorAll('[data-tabs]').forEach(function (list) {
        var buttons = list.querySelectorAll('[data-tab]');
        function show(name, focus) {
            buttons.forEach(function (b) {
                var on = b.getAttribute('data-tab') === name;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
                b.tabIndex = on ? 0 : -1;
                if (on && focus) b.focus();
            });
            document.querySelectorAll('[data-tab-panel]').forEach(function (p) {
                p.hidden = p.getAttribute('data-tab-panel') !== name;
            });
            // Gantt menghitung lebar saat terlihat
            if (name === 'rencana') window.dispatchEvent(new Event('resize'));
        }
        buttons.forEach(function (b, i) {
            b.addEventListener('click', function () {
                var name = b.getAttribute('data-tab');
                show(name, false);
                try { history.replaceState(null, '', name === 'catatan' ? '#catatan' : location.pathname + location.search); } catch (e) {}
            });
            b.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                var next = buttons[(i + (e.key === 'ArrowRight' ? 1 : buttons.length - 1)) % buttons.length];
                show(next.getAttribute('data-tab'), true);
            });
        });
        var wantNotes = list.getAttribute('data-tab-default') === 'catatan' || location.hash === '#catatan' || /[?&](p|periode)=/.test(location.search)
            || document.querySelector('[data-tab-panel="catatan"] .alert, [data-tab-panel="catatan"] .is-invalid');
        show(wantNotes ? 'catatan' : buttons[0].getAttribute('data-tab'), false);
    });
    document.querySelectorAll('[data-photo-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var label = document.querySelector('label[for="' + input.id + '"] [data-photo-name]');
            if (label) label.textContent = input.files.length ? input.files[0].name : 'Foto';
        });
    });

    // ---------- Kepala halaman: ikon menu yang sedang dibuka di samping judul ----------
    var menuIcon = document.querySelector('.crumb-root > .ikon, .crumb-root > svg, .crumb-root > span');
    if (menuIcon) {
        document.querySelectorAll('.page-head').forEach(function (head) {
            var title = head.querySelector(':scope > div > .page-title');
            if (!title || head.querySelector('.page-icon')) return;
            var box = title.parentElement;
            var icon = document.createElement('span');
            icon.className = 'page-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.appendChild(menuIcon.cloneNode(true));
            box.classList.add('page-head-main');
            box.insertBefore(icon, box.firstChild);
        });
    }

    // ---------- Sedikit kata: teks penjelasan panjang menjadi ikon info di samping judul ----------
    var tipId = 0;
    function closeTips(except) {
        document.querySelectorAll('.info-tip.is-open').forEach(function (t) {
            if (t !== except) { t.classList.remove('is-open'); t.querySelector('button').setAttribute('aria-expanded', 'false'); }
        });
    }
    function makeTip(el, title) {
        if (!title) return;
        var existing = title.querySelector('.info-tip-text');
        if (existing) { existing.insertAdjacentHTML('beforeend', '<br><br>' + el.innerHTML); el.remove(); return; }
        var id = 'tip-' + (++tipId);
        var wrap = document.createElement('span');
        wrap.className = 'info-tip';
        wrap.innerHTML = '<button type="button" class="info-tip-btn" aria-label="Keterangan" aria-expanded="false" aria-describedby="' + id + '">'
            + '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.5"/></svg></button>'
            + '<span class="info-tip-text" role="tooltip" id="' + id + '"></span>';
        wrap.lastChild.innerHTML = el.innerHTML;
        title.appendChild(wrap);
        el.remove();
        var btn = wrap.firstChild;
        btn.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            var open = !wrap.classList.contains('is-open');
            closeTips(wrap);
            wrap.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
    document.querySelectorAll('.page-sub, .card-sub, p.card-foot, .goal-note').forEach(function (el) {
        var text = el.textContent.trim();
        // keterangan sangat singkat, atau data singkat (berisi angka), tetap tampil
        if (el.matches('.page-sub, .card-sub') && (text.length <= 20 || (/\d/.test(text) && text.length <= 40))) return;
        if (el.querySelector('a, button, form, input, select')) return;     // ada kontrol di dalamnya: biarkan
        var title = null;
        if (el.matches('.page-sub')) title = el.parentNode.querySelector('.page-title');
        else if (el.matches('.card-sub')) title = el.parentNode.querySelector('.card-title');
        else { var card = el.closest('.card'); title = card && card.querySelector('.card-title'); }
        makeTip(el, title);
    });
    document.addEventListener('click', function () { closeTips(null); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeTips(null); });

    // ---------- Perayaan: konfeti sekali ----------
    function confetti() {
        if (reduceMotion) return;
        var colors = ['#0e9b7f', '#ffb020', '#3b82f6', '#ff7a59', '#8b5cf6'];
        var box = document.createElement('div');
        box.className = 'confetti';
        box.setAttribute('aria-hidden', 'true');
        for (var i = 0; i < 70; i++) {
            var p = document.createElement('i');
            p.style.left = (Math.random() * 100) + '%';
            p.style.background = colors[i % colors.length];
            p.style.setProperty('--x', ((Math.random() - 0.5) * 240) + 'px');
            p.style.setProperty('--r', (Math.random() * 720 - 360) + 'deg');
            p.style.setProperty('--delay', Math.round(Math.random() * 300) + 'ms');
            p.style.setProperty('--d', (1100 + Math.round(Math.random() * 700)) + 'ms');
            box.appendChild(p);
        }
        document.body.appendChild(box);
        setTimeout(function () { box.remove(); }, 2400);
    }
    window.simagtiConfetti = confetti;
    document.querySelectorAll('[data-celebrate]').forEach(function (el) {
        var key = 'simagti-rayakan-' + el.getAttribute('data-celebrate');
        try {
            if (localStorage.getItem(key)) return;
            localStorage.setItem(key, '1');
        } catch (e) { return; }
        setTimeout(confetti, 400);
    });

    // Filter: tombol "Filter" membuka pop-up pilihan; klik di luar atau Esc menutupnya
    document.querySelectorAll('[data-filter-toggle]').forEach(function (btn) {
        var form = btn.closest('[data-filters]');
        var pop = btn.closest('.filter-pop');
        function setOpen(open, focusBack) {
            form.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                var first = form.querySelector('.filter-panel select, .filter-panel input');
                if (first) first.focus();
            } else if (focusBack) {
                btn.focus();
            }
        }
        btn.addEventListener('click', function () { setOpen(!form.classList.contains('is-open'), false); });
        form.querySelectorAll('[data-filter-close]').forEach(function (c) {
            c.addEventListener('click', function () { setOpen(false, true); });
        });
        document.addEventListener('click', function (e) {
            if (form.classList.contains('is-open') && !pop.contains(e.target)) setOpen(false, false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && form.classList.contains('is-open')) setOpen(false, true);
        });
        // Rentang tanggal hanya tampil saat periode "Rentang tanggal" dipilih
        var periode = form.querySelector('[data-range-toggle]');
        var range = form.querySelector('.filter-range');
        if (periode && range) {
            periode.addEventListener('change', function () {
                var on = periode.value === 'rentang';
                range.hidden = !on;
                range.querySelectorAll('input').forEach(function (i) { i.disabled = !on; });
            });
        }
    });

    // Menu HP (drawer). Fokus pindah ke menu saat dibuka dan kembali ke tombol ☰ saat ditutup.
    var drawerBtn = document.querySelector('[data-drawer-open]');
    var drawer = document.getElementById('sidebar');
    function setDrawer(open) {
        if (!drawerBtn || !drawer) return;
        document.body.classList.toggle('drawer-open', open);
        drawerBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            var first = drawer.querySelector('.nav-item.is-active') || drawer.querySelector('a, button');
            if (first) setTimeout(function () { first.focus(); }, 30);
        } else {
            drawerBtn.focus();
        }
    }
    if (drawerBtn && drawer) {
        drawerBtn.addEventListener('click', function () { setDrawer(true); });
        document.querySelectorAll('[data-drawer-close]').forEach(function (el) {
            el.addEventListener('click', function () { setDrawer(false); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('drawer-open')) setDrawer(false);
        });
        window.matchMedia('(min-width: 901px)').addEventListener('change', function (m) {
            if (m.matches) document.body.classList.remove('drawer-open');
        });
    }

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
        form.addEventListener('change', function (e) {
            // Pilihan di pop-up filter menunggu tombol Terapkan
            if (e.target.closest('.filter-panel')) return;
            form.submit();
        });
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

    // Goal di samping board ikut berubah: penuh dan tercapai saat semua task-nya Done.
    function updateGoal(g) {
        if (!g) return;
        var row = document.querySelector('[data-goal="' + g.id + '"]');
        if (!row) return;
        var wasDone = row.classList.contains('is-done');
        row.classList.toggle('is-done', !!g.achieved);
        if (g.achieved && !wasDone) confetti();
        var bar = row.querySelector('[data-goal-bar]');
        if (bar) { bar.style.width = g.percent + '%'; bar.parentNode.setAttribute('aria-valuenow', g.percent); }
        var sub = row.querySelector('[data-goal-sub]');
        if (sub) sub.textContent = g.total > 0 ? g.done + '/' + g.total + ' task Done' : 'Belum ada task';
        var count = document.querySelector('[data-goals-done]');
        if (count) count.textContent = document.querySelectorAll('[data-goal].is-done').length;
    }

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
            updateGoal(data.goal);
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
        // Task terlipat bawaan; yang diingat adalah rencana yang dibuka pengguna.
        var expanded = {};
        try { expanded = JSON.parse(load('gt.expanded', '{}')) || {}; } catch (e) { expanded = {}; }

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
                if (open) expanded[id] = 1; else delete expanded[id];
                save('gt.expanded', JSON.stringify(expanded));
            }
        });

        Object.keys(expanded).forEach(function (id) { if (gt.querySelector('[data-gt-toggle="' + id + '"]')) toggle(id, true); });
        if (compact) { setZoom('week', false); }
        else { setView(load('gt.view', 'gantt'), false); setZoom(load('gt.zoom', 'week'), false); }

        // Lebar kartu bisa berubah setelah font/ikon dimuat atau saat layar diputar: hitung ulang mode Penuh.
        var lastWidth = scroll.clientWidth;
        function refit() { if (scroll.clientWidth !== lastWidth) { lastWidth = scroll.clientWidth; fit(); } }
        if ('ResizeObserver' in window) new ResizeObserver(refit).observe(scroll);
        else window.addEventListener('resize', refit);
        window.addEventListener('load', function () { fit(); scrollToToday(false); });
    });
})();

/* Pengaman animasi masuk: bila browser menahan animasi (tab tidak sedang digambar), tuntaskan setelah 1,2 detik
   supaya isi halaman tidak pernah tertinggal tak terlihat. */
setTimeout(function () {
    if (!document.getAnimations) return;
    document.getAnimations().forEach(function (a) {
        if (a.animationName === 'rise-in' && a.playState !== 'finished') { try { a.finish(); } catch (e) { /* abaikan */ } }
    });
}, 1200);

/* Angka kartu angka berhitung naik sekali saat halaman dibuka (hanya bilangan bulat, ±700 ms).
   Teks akhir sama persis dengan isi dari server; dilewati bila pengguna memilih kurangi gerak. */
(function () {
    if (!window.requestAnimationFrame) return;
    try { if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return; } catch (e) { return; }
    if (document.visibilityState !== 'visible') return;
    var ease = function (t) { return 1 - Math.pow(1 - t, 4); };
    document.querySelectorAll('.stat-value, .kpp-fig strong').forEach(function (el) {
        var node = el.firstChild;
        if (!node || node.nodeType !== 3) return;
        var text = node.textContent.trim();
        if (!/^\d{1,4}$/.test(text)) return;
        var target = parseInt(text, 10);
        if (target < 2) return;
        var start = null, dur = 700;
        node.textContent = '0';
        function step(ts) {
            if (start === null) start = ts;
            var t = Math.min(1, (ts - start) / dur);
            node.textContent = String(Math.round(target * ease(t)));
            if (t < 1) requestAnimationFrame(step); else node.textContent = text;
        }
        setTimeout(function () { requestAnimationFrame(step); }, 150);
        // Cadangan: tab di latar belakang menahan requestAnimationFrame; angka akhir tetap dipasang.
        setTimeout(function () { node.textContent = text; }, dur + 400);
    });
})();
