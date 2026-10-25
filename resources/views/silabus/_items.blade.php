{{-- Tiga kelompok tujuan silabus. Variabel: $internship, $canEdit --}}
<div class="syllabus-grid">
    @foreach (\App\Models\SyllabusItem::CATEGORIES as $category => [$label, $hint, $icon, $tone])
        @php($items = $internship->syllabusItems->where('category', $category))
        <section class="card syllabus-col">
            <div class="card-head">
                <div class="syllabus-head">
                    <span class="syllabus-icon tone-bg-{{ $tone }}"><x-ikon :name="$icon" :size="20" /></span>
                    <div>
                        <h2 class="card-title">{{ $label }}</h2>
                        <p class="card-sub">{{ $hint }}</p>
                    </div>
                </div>
                <span class="count-badge">{{ $items->count() }}</span>
            </div>

            <ul class="syllabus-list">
                @forelse ($items as $item)
                    <li class="syllabus-item">
                        <div class="syllabus-item-main">
                            <div class="cell-title">{{ $item->title }}</div>
                            @if ($item->description)
                                <p class="syllabus-desc">{{ $item->description }}</p>
                            @endif
                            @if ($item->tasks->isNotEmpty())
                                <span class="syllabus-tasks"><x-ikon name="tasks" :size="13" /> {{ $item->tasks->count() }} task · {{ $item->tasks->where('status', 'completed')->count() }} selesai</span>
                            @endif
                        </div>
                        @if ($canEdit)
                            <details class="item-menu">
                                <summary class="btn btn-sm btn-icon btn-ghost" aria-label="Ubah {{ $item->title }}"><x-ikon name="edit" :size="16" /></summary>
                                <form method="POST" action="{{ route('silabus.update', $item) }}" class="stack item-menu-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="category" value="{{ $category }}">
                                    <input type="text" name="title" class="input" value="{{ $item->title }}" maxlength="200" required aria-label="Tujuan">
                                    <textarea name="description" class="textarea" rows="2" maxlength="1000" aria-label="Keterangan">{{ $item->description }}</textarea>
                                    <div class="inline-form">
                                        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                        <button type="submit" form="del-syl-{{ $item->id }}" class="btn btn-sm btn-ghost-danger">Hapus</button>
                                    </div>
                                </form>
                                <form id="del-syl-{{ $item->id }}" method="POST" action="{{ route('silabus.destroy', $item) }}" onsubmit="return confirm('Hapus tujuan ini?')">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </details>
                        @endif
                    </li>
                @empty
                    <li class="syllabus-empty">Belum ada tujuan.</li>
                @endforelse
            </ul>

            @if ($canEdit)
                <form method="POST" action="{{ route('silabus.store') }}" class="syllabus-add">
                    @csrf
                    <input type="hidden" name="category" value="{{ $category }}">
                    <input type="text" name="title" class="input" maxlength="200" required placeholder="Tambah tujuan {{ mb_strtolower($label) }}…" aria-label="Tujuan {{ $label }} baru">
                    <button type="submit" class="btn btn-sm btn-icon" aria-label="Tambah"><x-ikon name="plus" :size="18" /></button>
                </form>
            @endif
        </section>
    @endforeach
</div>
