{{--
    Kolom Goals di board (paling kiri, di samping To Do). Gaya sama dengan kolom task.
    Kartu goal: judul, jumlah task Done, bar progres, lencana "Tercapai". Bar dan lencana diperbarui app.js
    saat task digeser (atribut data-goal, data-goal-bar, data-goal-sub, data-goals-done).
    Variabel: $goals, $canEdit
--}}
<section class="kcol kcol-goals" data-goals aria-label="Goals">
    <header class="kcol-head">
        <span class="kcol-goal-icon" aria-hidden="true"><x-ikon name="flag" :size="14" /></span>
        <h3 class="kcol-title">Goals</h3>
        <span class="kcol-count kcol-count-wide" title="Goal tercapai"><span data-goals-done>{{ $goals->whereNotNull('achieved_at')->count() }}</span>/{{ $goals->count() }}<span class="sr-only"> (dari {{ $goals->count() }} tercapai)</span></span>
        @if ($canEdit)
            <button type="button" class="btn btn-sm btn-icon kcol-add-btn" data-open="dlg-goal" aria-label="Tambah goal"><x-ikon name="plus" :size="16" /></button>
        @endif
    </header>
    <div class="kcol-body">
        @forelse ($goals as $goal)
            @php($pct = $goal->percent())
            <article @class(['gcard', 'is-done' => $goal->isAchieved()]) data-goal="{{ $goal->id }}">
                <div class="gcard-top">
                    <span class="gcard-tag"><x-ikon name="flag" :size="12" /> Goal</span>
                    <span class="goal-badge">Tercapai</span>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('goal.destroy', $goal) }}" onsubmit="return confirm('Hapus goal ini? Task yang terkait tetap ada.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-ghost" aria-label="Hapus goal {{ $goal->title }}"><x-ikon name="delete" :size="14" /></button>
                        </form>
                    @endif
                </div>
                <a href="{{ request()->fullUrlWithQuery(['goal' => $goal->id]) }}" class="gcard-title" title="Tampilkan task goal ini">{{ $goal->title }}</a>
                <div class="gcard-progress">
                    <div class="kcard-progress-top"><span>Progres</span><b data-goal-sub>{{ $goal->tasks_count > 0 ? $goal->completed_tasks_count.'/'.$goal->tasks_count.' task Done' : 'Belum ada task' }}</b></div>
                    <div class="goal-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Task Done"><span data-goal-bar style="width: {{ $pct }}%"></span></div>
                </div>
            </article>
        @if ($loop->last)
                <p class="kcol-note">Goal tercapai otomatis saat semua task-nya sudah Done.</p>
            @endif
        @empty
            <p class="kcol-empty">{{ $canEdit ? 'Tulis hal yang ingin dicapai selama KP.' : 'Belum ada goal.' }}</p>
        @endforelse
    </div>
</section>
