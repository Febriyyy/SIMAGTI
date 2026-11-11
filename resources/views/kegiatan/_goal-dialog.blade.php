{{-- Dialog Goal baru (dipakai kolom Goals di board dan kartu Goals di tampilan List) --}}
<dialog id="dlg-goal" class="dialog" @if ($errors->has('title') && old('_goal')) data-autoopen @endif>
    <form method="POST" action="{{ route('goal.store') }}" class="dialog-body">
        @csrf
        <input type="hidden" name="_goal" value="1">
        <div class="dialog-head">
            <h2 class="card-title">Goal baru</h2>
            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
        </div>
        <div class="stack">
            <div>
                <label class="label" for="goal-title">Goal</label>
                <input id="goal-title" type="text" name="title" class="input" maxlength="160" required value="{{ old('_goal') ? old('title') : '' }}" placeholder="Misalnya Mampu membuat REST API dengan Laravel">
            </div>
            <div>
                <label class="label" for="goal-desc">Keterangan (opsional)</label>
                <textarea id="goal-desc" name="description" class="textarea" rows="3" maxlength="2000">{{ old('_goal') ? old('description') : '' }}</textarea>
            </div>
        </div>
        <div class="dialog-foot row-actions">
            <button type="button" class="btn" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</dialog>
