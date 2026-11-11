{{--
    Rencana kegiatan dalam dua tampilan: Timeline (bawaan, lajur melayang) dan Tabel (Gantt klasik dengan kolom Durasi/Status).
    Pilihan diingat per perangkat (app.js). Tanpa JavaScript keduanya tampil.
    Variabel: $gantt, $canEdit
--}}
<div class="plan-views" data-plan-views>
    <div data-plan-panel="timeline">@include('timesheet._timeline', ['gantt' => $gantt, 'canEdit' => $canEdit, 'viewSwitch' => true])</div>
    <div data-plan-panel="table">@include('timesheet._gantt', ['canEdit' => $canEdit, 'viewSwitch' => true])</div>
</div>
