<tr>
    <td><input type="text" name="aspects[{{ $i }}][name]" class="input input-sm" maxlength="100" value="{{ $a['name'] ?? '' }}" data-name aria-label="Nama aspek"></td>
    <td><input type="text" name="aspects[{{ $i }}][description]" class="input input-sm" maxlength="300" value="{{ $a['description'] ?? '' }}" aria-label="Keterangan"></td>
    <td>
        <select name="aspects[{{ $i }}][kind]" class="select select-sm" data-kind aria-label="Jenis">
            <option value="score" @selected(($a['kind'] ?? 'score') === 'score')>Nilai</option>
            <option value="text" @selected(($a['kind'] ?? '') === 'text')>Isian</option>
        </select>
    </td>
    <td><input type="number" name="aspects[{{ $i }}][weight]" class="input input-sm num" min="0" max="100" step="0.5" value="{{ ($a['kind'] ?? 'score') === 'score' && isset($a['weight']) && $a['weight'] !== '' ? (float) $a['weight'] : '' }}" data-weight aria-label="Bobot"></td>
    <td><button type="button" class="btn btn-sm btn-ghost" data-remove aria-label="Hapus baris"><x-ikon name="delete" :size="16" /></button></td>
</tr>
