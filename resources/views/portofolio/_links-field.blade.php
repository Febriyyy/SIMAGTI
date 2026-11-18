{{-- Isian beberapa tautan (proyek/sertifikat). Variabel: id, links (array url), e (error bag), hint --}}
<?php $rows = $links ?: ['']; ?>
<div class="span-all" data-pf-links>
    <label class="label" for="{{ $id }}-link-0">Tautan</label>
    <ul class="pf-links" data-pf-links-list>
        @foreach ($rows as $i => $url)
            <li class="pf-link-row">
                <input id="{{ $id }}-link-{{ $i }}" name="links[]" type="url" class="input" maxlength="255" value="{{ $url }}" placeholder="https://github.com/nama/proyek">
                <button type="button" class="btn btn-sm btn-icon btn-ghost" data-pf-link-remove aria-label="Hapus tautan">×</button>
            </li>
        @endforeach
    </ul>
    <button type="button" class="btn btn-sm pf-links-add" data-pf-link-add><x-ikon name="plus" :size="16" /> Tambah tautan</button>
    <p class="hint">{{ $hint }} Paling banyak 5 tautan.</p>
    @foreach (['links', 'links.*', 'url', 'credential_url'] as $key)
        @if ($e->has($key))<p class="field-error">{{ $e->first($key) }}</p>@break @endif
    @endforeach
    @foreach ($e->keys() as $key)
        @if (str_starts_with($key, 'links.'))<p class="field-error">{{ $e->first($key) }}</p>@break @endif
    @endforeach
    <template data-pf-link-template>
        <li class="pf-link-row">
            <input name="links[]" type="url" class="input" maxlength="255" placeholder="https://">
            <button type="button" class="btn btn-sm btn-icon btn-ghost" data-pf-link-remove aria-label="Hapus tautan">×</button>
        </li>
    </template>
</div>
