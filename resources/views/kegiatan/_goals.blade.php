{{-- Goals di samping board Kegiatan (dan di halaman pantau). Variabel: $goals (withCount tasks + completed_tasks_count), $canEdit.
     Tercapai otomatis bila semua task-nya Done (Goal::syncAchievement); bar diperbarui app.js saat task dipindah di board. --}}
<section class="card goals-card" data-goals>
    <div class="card-head">
        <div>
            <h2 class="card-title">Goals</h2>
            <p class="card-sub"><span data-goals-done>{{ $goals->whereNotNull('achieved_at')->count() }}</span> dari {{ $goals->count() }} tercapai</p>
        </div>
        @if ($canEdit)
            <button type="button" class="btn btn-sm" data-open="dlg-goal"><x-ikon name="plus" :size="16" /> Goal</button>
        @endif
    </div>
    @if ($goals->isEmpty())
        <div class="empty">
            <strong>Belum ada goal</strong>
            Tulis target KP, lalu kaitkan task.
        </div>
    @else
        <ul class="goal-list">
            @foreach ($goals as $goal)
                @php($pct = $goal->percent())
                <li @class(['goal', 'is-done' => $goal->isAchieved()]) data-goal="{{ $goal->id }}">
                    <span class="goal-state" title="{{ $goal->isAchieved() ? 'Tercapai' : 'Belum tercapai' }}"><x-ikon name="tick" :size="14" /></span>
                    <div class="goal-text">
                        <a href="{{ request()->fullUrlWithQuery(['goal' => $goal->id]) }}" class="goal-title" title="Tampilkan task goal ini">{{ $goal->title }}</a>
                        <span class="goal-sub" data-goal-sub>{{ $goal->tasks_count > 0 ? $goal->completed_tasks_count.'/'.$goal->tasks_count.' task Done' : 'Belum ada task' }}</span>
                        <span class="goal-badge">Tercapai</span>
                        <div class="goal-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Task Done"><span data-goal-bar style="width: {{ $pct }}%"></span></div>
                    </div>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('goal.destroy', $goal) }}" onsubmit="return confirm('Hapus goal ini? Task yang terkait tetap ada.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-ghost" aria-label="Hapus goal {{ $goal->title }}"><x-ikon name="delete" :size="16" /></button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="goal-note">Goal tercapai otomatis saat semua task-nya sudah Done.</p>
    @endif
</section>

@if ($canEdit)
    @include('kegiatan._goal-dialog')
@endif
