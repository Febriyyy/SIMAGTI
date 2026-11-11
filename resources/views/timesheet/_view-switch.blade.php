{{-- Pilihan tampilan rencana Timeline / Tabel (ditaruh di toolbar masing-masing tampilan; diatur app.js) --}}
<div class="segmented plan-views-switch" role="group" aria-label="Tampilan rencana">
    <button type="button" class="seg" data-plan-view="timeline"><x-ikon name="activity" :size="16" /> Timeline</button>
    <button type="button" class="seg" data-plan-view="table"><x-ikon name="list" :size="16" /> Tabel</button>
</div>
