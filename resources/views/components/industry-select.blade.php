{{--
    Bidang usaha perusahaan: pilihan tetap (Company::INDUSTRIES) + "Lainnya…" yang diisi sendiri.
    Mengirim industry (pilihan atau "lainnya") dan industry_other (isian bebas). Request menggabungkannya.
    value: bidang usaha yang tersimpan, required: wajib dipilih
--}}
@props(['value' => null, 'required' => false])
@php
    $other = \App\Models\Company::INDUSTRY_OTHER;
    $current = old('industry_choice') === $other ? $other : old('industry', $value);
    $isOther = $current && ! in_array($current, \App\Models\Company::INDUSTRIES, true);
    $otherText = old('industry_other', $isOther && $current !== $other ? $current : '');
@endphp
<div>
    <label for="industry" class="label">Bidang usaha</label>
    <select id="industry" name="industry" class="select" @if ($required) required @endif data-other-toggle="industry-other-field">
        <option value="" @disabled($required) @selected(! $current)>{{ $required ? 'Pilih bidang usaha…' : 'Belum diisi' }}</option>
        @foreach (\App\Models\Company::INDUSTRIES as $option)
            <option value="{{ $option }}" @selected($current === $option)>{{ $option }}</option>
        @endforeach
        <option value="{{ $other }}" @selected($isOther)>Lainnya…</option>
    </select>
</div>
<div id="industry-other-field" @unless ($isOther) hidden @endunless>
    <label for="industry_other" class="label">Bidang usaha lainnya</label>
    <input id="industry_other" name="industry_other" type="text" class="input" maxlength="100" value="{{ $otherText }}" placeholder="Misalnya Kesehatan, Media, Logistik">
</div>
