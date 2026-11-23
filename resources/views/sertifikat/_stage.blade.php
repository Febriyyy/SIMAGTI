{{--
    Studio sertifikat: sertifikat ukuran besar (sama dengan versi cetak) yang bagiannya bisa diisi langsung.
    Bagian milik peran yang membuka halaman bisa diklik/diketik; bagian lain redup dengan keterangan pengisinya.

    Variabel:
      $edit    : ['kampus' => id form|null, 'fakultas' => id form|null, 'perusahaan' => id form|null]
      $company : Company|null (logo perusahaan)
      $left    : blok tanda tangan kiri (ketua divisi)
      $right   : blok tanda tangan kanan (kaprodi)
        tiap blok: role, name, sub, sign (data URI|null), owner (teks "Diisi …"),
                   form (id form bila bisa diedit), nameField, titleField (opsional), live (kunci pratinjau)
    Isian memakai atribut form="…" sehingga bisa berada di dalam sertifikat walau form-nya di luar.
--}}
<?php
    $campusLogo = \App\Support\Logos::campus();
    $facultyLogo = \App\Support\Logos::faculty();
    $logos = [
        'kampus' => ['Logo kampus', $campusLogo ? route('logo.kampus').'?v='.$campusLogo->id : null],
        'fakultas' => ['Logo fakultas', $facultyLogo ? route('logo.fakultas').'?v='.$facultyLogo->id : null],
        'perusahaan' => ['Logo perusahaan', $company?->logo_file_id ? route('logo.perusahaan', $company).'?v='.$company->logo_file_id : null],
    ];
    $owners = ['kampus' => 'Diisi admin', 'fakultas' => 'Diisi admin', 'perusahaan' => 'Diisi perusahaan'];
?>
<div class="cst">
    <div class="cst-paper">
    <div class="cst-sheet">
        <span class="cst-frame" aria-hidden="true"><i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i></span>

        <div class="cst-logos">
            @foreach ($logos as $key => [$label, $url])
                @if ($key === 'perusahaan')<span class="cst-divider" aria-hidden="true"></span>@endif
                @if ($edit[$key] ?? null)
                    <label @class(['cst-slot', 'is-mine', 'has-img' => $url])>
                        @if ($url)
                            <img src="{{ $url }}" alt="{{ $label }}">
                        @else
                            <span class="cst-slot-text"><b>{{ $label }}</b>Klik untuk unggah</span>
                        @endif
                        <span class="cst-tip">{{ $url ? 'Ganti' : 'Unggah' }} {{ mb_strtolower($label) }}</span>
                        <input type="file" name="logo" form="{{ $edit[$key] }}" accept="image/png,image/jpeg,image/webp" class="sr-only" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" aria-label="Unggah {{ mb_strtolower($label) }}">
                    </label>
                @elseif ($url)
                    <img class="cst-logo" src="{{ $url }}" alt="{{ $label }}">
                @else
                    <span class="cst-slot is-locked"><span class="cst-slot-text"><b>{{ $label }}</b>{{ $owners[$key] }}</span></span>
                @endif
            @endforeach
        </div>
        <p class="cst-campus">{{ \App\Support\Campus::university() }} · {{ \App\Support\Campus::faculty() }}</p>

        <p class="cst-title">Sertifikat Kerja Praktik</p>
        <p class="cst-given">diberikan kepada</p>
        <p class="cst-name">Nama Mahasiswa</p>
        <span class="cst-rule" aria-hidden="true"><i></i><b></b><i></i></span>
        <span class="cst-lines" aria-hidden="true"><i></i><i></i></span>
        <span class="cst-grade" aria-hidden="true"><span>Nilai akhir</span><b>A</b><span>91 / 100</span></span>

        <div class="cst-signs">
            @foreach ([$left, 'qr', $right] as $s)
                @if ($s === 'qr')
                    <span class="cst-qr" aria-hidden="true"></span>
                    @continue
                @endif
                <?php $mine = ! empty($s['form']); ?>
                <div @class(['cst-sign', 'is-mine' => $mine, 'is-locked' => ! $mine])>
                    @if ($mine && ! empty($s['titleField']))
                        <input class="cst-inline cst-role" type="text" name="{{ $s['titleField'] }}" form="{{ $s['form'] }}" value="{{ old($s['titleField'], $s['role']) }}" maxlength="100" required aria-label="Jabatan">
                    @else
                        <span class="cst-role">{{ $s['role'] }}</span>
                    @endif

                    @if ($mine)
                        <label class="cst-ink is-mine">
                            <img src="{{ $s['sign'] ?? '' }}" alt="Tanda tangan" data-live-sign-out="{{ $s['live'] }}" @if (empty($s['sign'])) hidden @endif>
                            @if (empty($s['sign']))<span class="cst-ink-empty" data-live-sign-empty="{{ $s['live'] }}">Klik untuk unggah tanda tangan</span>@endif
                            <span class="cst-tip">{{ empty($s['sign']) ? 'Unggah' : 'Ganti' }} tanda tangan</span>
                            <input type="file" name="signature" form="{{ $s['form'] }}" accept="image/png,image/jpeg,image/webp" class="sr-only" data-live-sign="{{ $s['live'] }}" aria-label="Unggah tanda tangan">
                        </label>
                        <input class="cst-inline cst-signer" type="text" name="{{ $s['nameField'] }}" form="{{ $s['form'] }}" value="{{ old($s['nameField'], $s['name']) }}" maxlength="150" required placeholder="Ketik nama beserta gelar" aria-label="Nama penanda tangan">
                    @else
                        <span class="cst-ink"></span>
                        <span class="cst-signer is-placeholder">{{ $s['owner'] }}</span>
                    @endif
                    <span class="cst-sub">{{ $s['sub'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
    </div>
    @if (! empty($shapes))
        @include('sertifikat._legend', ['shapes' => $shapes])
    @endif
</div>

@once
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;1,9..144,400&display=swap" rel="stylesheet">
    <script>
        // Tanda tangan yang baru dipilih langsung tampil di sertifikat sebelum disimpan
        document.addEventListener('change', function (e) {
            var k = e.target.getAttribute && e.target.getAttribute('data-live-sign');
            var f = e.target.files && e.target.files[0];
            if (!k || !f) return;
            var url = URL.createObjectURL(f);
            document.querySelectorAll('[data-live-sign-out="' + k + '"]').forEach(function (img) { img.src = url; img.hidden = false; });
            document.querySelectorAll('[data-live-sign-empty="' + k + '"]').forEach(function (el) { el.hidden = true; });
        });
    </script>
@endonce
