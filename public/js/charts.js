/*
 * Tooltip dan sorotan untuk grafik SIMAGTI (diagram lingkaran dan garis).
 * Tanpa library. Elemen dengan atribut data-tip menampilkan tooltip;
 * baris legenda/bagian donut dengan data-key menyorot bagian yang sama.
 */
(function () {
    var tip = document.createElement('div');
    tip.className = 'chart-tip';
    tip.setAttribute('role', 'tooltip');
    document.body.appendChild(tip);

    function render(text) {
        tip.textContent = '';
        text.split('\n').forEach(function (line, i) {
            var row = document.createElement('div');
            row.textContent = line;
            if (i === 0 && text.indexOf('\n') !== -1) row.className = 'chart-tip-title';
            tip.appendChild(row);
        });
    }

    function place(e) {
        var pad = 14, w = tip.offsetWidth, h = tip.offsetHeight;
        var left = e.clientX + pad, top = e.clientY - h - pad;
        if (left + w > window.innerWidth - 8) left = e.clientX - w - pad;
        if (top < 8) top = e.clientY + pad;
        tip.style.transform = 'translate(' + Math.round(left) + 'px,' + Math.round(top) + 'px)';
    }

    function focus(chart, key) {
        chart.querySelectorAll('[data-key]').forEach(function (el) {
            el.classList.toggle('is-dim', key !== null && el.getAttribute('data-key') !== key);
        });
    }

    var current = null;
    document.addEventListener('pointerover', function (e) {
        var target = e.target.closest ? e.target.closest('[data-tip]') : null;
        if (target !== current) {
            current = target;
            if (target) { render(target.getAttribute('data-tip')); tip.classList.add('is-on'); place(e); }
            else tip.classList.remove('is-on');
        }
        var keyed = e.target.closest ? e.target.closest('[data-chart] [data-key]') : null;
        document.querySelectorAll('[data-chart]').forEach(function (chart) {
            focus(chart, keyed && chart.contains(keyed) ? keyed.getAttribute('data-key') : null);
        });
    });
    document.addEventListener('pointermove', function (e) { if (current) place(e); });
    window.addEventListener('scroll', function () { current = null; tip.classList.remove('is-on'); }, { passive: true });
})();
