{{--
    Satu item status di bilah bawah studio sertifikat.
    Variabel: icon, title, sub, ok (bool), okLabel, todoLabel, messages (array pesan galat, opsional),
              delete (url DELETE, opsional) atau removeForm + removeName (id form dan nama isian penghapus),
              deleteLabel, confirm (teks konfirmasi)
--}}
<div @class(['cst-item', 'is-ok' => $ok])>
    <span class="cst-item-icon"><x-ikon :name="$ok ? 'tick' : $icon" :size="16" /></span>
    <div class="cst-item-body">
        <strong>{{ $title }}</strong>
        <span class="cst-item-meta">
            <span @class(['cst-state', 'is-ok' => $ok])>{{ $ok ? $okLabel : $todoLabel }}</span>
            <span class="cst-item-sub">{{ $sub }}</span>
        </span>
        @foreach (array_filter($messages ?? []) as $message)
            <p class="field-error">{{ $message }}</p>
        @endforeach
    </div>
    @if (! empty($delete))
        <form method="POST" action="{{ $delete }}" onsubmit="return confirm(@js($confirm))">
            @csrf
            @method('DELETE')
            <button type="submit" class="cst-del" aria-label="{{ $deleteLabel }}" title="{{ $deleteLabel }}"><x-ikon name="delete" :size="18" /></button>
        </form>
    @elseif (! empty($removeForm))
        {{-- Hapus lewat form simpan milik item ini (mis. remove_signature=1) --}}
        <button type="submit" form="{{ $removeForm }}" name="{{ $removeName }}" value="1" formnovalidate class="cst-del" aria-label="{{ $deleteLabel }}" title="{{ $deleteLabel }}" onclick="return confirm(@js($confirm))"><x-ikon name="delete" :size="18" /></button>
    @endif
</div>
