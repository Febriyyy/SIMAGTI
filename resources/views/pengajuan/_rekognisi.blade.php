{{-- Rekognisi SKS MBKM: usulan mata kuliah yang disetarakan, diputuskan kaprodi. --}}
@php
    $editable = $internship->status === 'berjalan' && $recognition?->status !== 'disetujui';
    $rows = old('courses', $recognition?->courses ?? []);
    $rows = array_pad(array_values($rows), max(3, count($rows) + 1), ['code' => '', 'name' => '', 'credits' => '']);
@endphp
<section class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Rekognisi SKS</h2>
            <p class="card-sub">Mata kuliah yang disetarakan dari magang MBKM, maksimal {{ \App\Models\CreditRecognition::MAX_CREDITS }} SKS</p>
        </div>
        @if ($recognition)<x-chip :tone="$recognition->statusTone()">{{ $recognition->statusLabel() }}</x-chip>@endif
    </div>
    <div class="card-body stack">
        @if ($recognition?->status === 'ditolak' && $recognition->note)
            <div class="alert alert-warning" role="status"><strong>Ditolak kaprodi:</strong> {{ $recognition->note }}</div>
        @endif
        @if ($editable)
            <form method="POST" action="{{ route('rekognisi.update') }}" class="stack">
                @csrf
                @method('PUT')
                <div class="course-rows">
                    @foreach ($rows as $i => $c)
                        <div class="course-row">
                            <input type="text" name="courses[{{ $i }}][code]" class="input input-sm" maxlength="20" value="{{ $c['code'] ?? '' }}" placeholder="Kode" aria-label="Kode mata kuliah {{ $i + 1 }}">
                            <input type="text" name="courses[{{ $i }}][name]" class="input input-sm" maxlength="120" value="{{ $c['name'] ?? '' }}" placeholder="Nama mata kuliah" aria-label="Nama mata kuliah {{ $i + 1 }}">
                            <input type="number" name="courses[{{ $i }}][credits]" class="input input-sm num" min="1" max="6" value="{{ $c['credits'] ?? '' }}" placeholder="SKS" aria-label="SKS {{ $i + 1 }}">
                        </div>
                    @endforeach
                </div>
                <p class="hint">Baris yang namanya kosong diabaikan. Usulan bisa diubah sampai disetujui kaprodi.</p>
                <div class="row-actions"><button type="submit" class="btn btn-primary btn-sm"><x-ikon name="send" :size="16" /> {{ $recognition ? 'Kirim ulang usulan' : 'Kirim usulan' }}</button></div>
            </form>
        @elseif ($recognition)
            <ul class="plain-list">
                @foreach ($recognition->courses as $c)<li>{{ $c['code'] ? $c['code'].' · ' : '' }}{{ $c['name'] }} <span class="muted">({{ $c['credits'] }} SKS)</span></li>@endforeach
            </ul>
            <p class="muted">Total {{ $recognition->totalCredits() }} SKS</p>
        @else
            <p class="muted">Tidak ada usulan rekognisi.</p>
        @endif
    </div>
</section>
