{{--
    Banner sapaan di atas dashboard. Sama untuk semua peran; isi dan tombolnya menyesuaikan.
    eyebrow: label kecil (peran), title: sapaan, sub: ringkasan satu kalimat,
    action/href: tombol menuju halaman yang paling perlu dibuka peran ini (opsional).
--}}
@props(['eyebrow' => null, 'title', 'sub' => null, 'action' => null, 'href' => null])
<section class="hero">
    <svg class="hero-art" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><path d="M50 0C53 34 66 47 100 50 66 53 53 66 50 100 47 66 34 53 0 50 34 47 47 34 50 0z"/></svg>
    <svg class="hero-art hero-art-2" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><path d="M50 0C53 34 66 47 100 50 66 53 53 66 50 100 47 66 34 53 0 50 34 47 47 34 50 0z"/></svg>
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
