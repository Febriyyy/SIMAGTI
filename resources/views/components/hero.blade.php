{{--
    Banner sapaan di atas dashboard. Sama untuk semua peran; isi dan tombolnya menyesuaikan.
    eyebrow: label kecil (peran), title: sapaan, sub: ringkasan satu kalimat,
    action/href: tombol menuju halaman yang paling perlu dibuka peran ini (opsional).
--}}
@props(['eyebrow' => null, 'title', 'sub' => null, 'action' => null, 'href' => null])
<section class="hero">
    <img class="hero-mark" src="{{ asset('images/logo-simagti-mark.svg') }}" alt="" aria-hidden="true">
    <div class="hero-text">
        @if ($eyebrow)<div class="hero-eyebrow">{{ $eyebrow }}</div>@endif
        <h1 class="hero-title">{{ $title }}</h1>
        @if ($sub)<p class="hero-sub">{{ $sub }}</p>@endif
    </div>
    @if ($action && $href)
        <div class="hero-action">
            <a href="{{ $href }}" class="btn btn-dark">{{ $action }} <span class="btn-dark-icon"><x-icon name="arrow" :size="14" /></span></a>
        </div>
    @endif
</section>
