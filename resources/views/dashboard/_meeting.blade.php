{{-- Meeting minggu ini + form atur meeting. Variabel: $internships, $groups, $withoutMeeting --}}
<section class="card">
    <div class="card-head">
        <h2 class="card-title">Meeting minggu ini</h2>
    </div>

    @foreach ($groups as $group)
        @php($m = $group['meeting'])
        <div class="meeting">
            <div class="meeting-when">{{ $m->dayLabel() }}, {{ $m->timeLabel() }}</div>
            <div class="meeting-who">
                @foreach ($group['students'] as $student)
                    {{ $student->listName() }}@if (! $loop->last), @endif
                @endforeach
            </div>
            <a href="{{ $m->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm">
                <x-ikon name="video" :size="16" /> Buka link
            </a>
        </div>
    @endforeach

    @if (count($withoutMeeting) > 0)
        <p class="meeting-empty" @if (count($groups) > 0) style="padding-top: var(--space-4)" @endif>
            Belum dijadwalkan:
            @foreach ($withoutMeeting as $student)
                {{ $student->listName() }}@if (! $loop->last), @endif
            @endforeach
        </p>
    @elseif (count($groups) === 0)
        <p class="meeting-empty">Belum ada mahasiswa.</p>
    @endif
</section>

@if ($internships->isNotEmpty())
    <details class="card" @if ($errors->hasAny(['magang', 'day', 'time', 'url'])) open @endif>
        <summary>Atur meeting</summary>
        <div class="card-body">
            <form method="POST" action="{{ route('meeting.store') }}" class="stack">
                @csrf

                <div>
                    <span class="label">Mahasiswa</span>
                    <div class="check-list">
                        @foreach ($internships as $internship)
                            <label class="check">
                                <input type="checkbox" name="magang[]" value="{{ $internship->id }}" @checked(in_array($internship->id, (array) old('magang', $internships->pluck('id')->all())))>
                                {{ $internship->student->name }} ({{ $internship->student->nim }})
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                        <label for="day" class="label">Hari</label>
                        <select id="day" name="day" class="select">
                            @foreach (\App\Models\WeeklyTask::DAYS as $key => $label)
                                <option value="{{ $key }}" @selected(old('day') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="time" class="label">Jam</label>
                        <input id="time" name="time" type="time" class="input" value="{{ old('time', '10:00') }}" required>
                    </div>
                </div>

                <div>
                    <label for="url" class="label">Link Zoom atau Google Meet</label>
                    <input id="url" name="url" type="url" class="input" value="{{ old('url') }}" placeholder="https://meet.google.com/..." required>
                </div>

                <button type="submit" class="btn btn-primary">Simpan jadwal</button>
            </form>
        </div>
    </details>
@endif
