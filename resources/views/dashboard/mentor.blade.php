@extends('layouts.app')

@section('title', 'Dashboard Mentor')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ \App\Support\Greeting::now() }}, {{ $user->firstName() }}</h1>
            <p class="page-sub">Pantau mahasiswa bimbingan Anda.</p>
        </div>
        <a href="{{ route('mentor.tugas') }}" class="btn btn-primary">
            <x-icon name="plus" :size="16" /> Kelola task
        </a>
    </div>

    <div class="pills">
        <span class="pill"><x-icon name="users" :size="16" /> Mahasiswa bimbingan <strong>{{ $stats['students'] }}</strong></span>
        <span class="pill"><x-icon name="chat" :size="16" /> Perlu ditinjau <strong>{{ $stats['review'] }}</strong></span>
        <span class="pill"><x-icon name="tasks" :size="16" /> Task selesai <strong>{{ $stats['done'] }}</strong></span>
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Mahasiswa bimbingan</h2>
                </div>

                @if ($internships->isEmpty())
                    <div class="empty">
                        <strong>Belum ada mahasiswa bimbingan</strong>
                        Mahasiswa akan muncul setelah penempatan magangnya aktif.
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mahasiswa</th>
                                    <th>Perusahaan</th>
                                    <th>Progres task</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($internships as $internship)
                                    <tr>
                                        <td><x-person :user="$internship->student" /></td>
                                        <td>{{ $internship->company->name }}</td>
                                        <td><x-progress :value="$internship->percent()" /></td>
                                        <td class="col-action">
                                            <a href="{{ route('mentor.tugas', ['magang' => $internship->id]) }}" class="btn btn-sm">Kelola</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="col">
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
                                {{ $student->name }} ({{ $student->nim }})@if (! $loop->last), @endif
                            @endforeach
                        </div>
                        <a href="{{ $m->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm">
                            <x-icon name="video" :size="16" /> Buka link
                        </a>
                    </div>
                @endforeach

                @if (count($withoutMeeting) > 0)
                    <p class="meeting-empty" style="padding-top: 16px">
                        Belum dijadwalkan:
                        @foreach ($withoutMeeting as $student)
                            {{ $student->name }} ({{ $student->nim }})@if (! $loop->last), @endif
                        @endforeach
                    </p>
                @endif
            </section>

            @if ($internships->isNotEmpty())
                <details class="card" @if ($errors->hasAny(["magang", "day", "time", "url"])) open @endif>
                    <summary>Atur meeting</summary>
                    <div class="card-body">
                        <form method="POST" action="{{ route('mentor.meeting.store') }}" class="stack">
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

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Perlu ditinjau</h2>
                        <p class="card-sub">Bukti sudah dikirim, belum ada komentar</p>
                    </div>
                </div>
                @if ($review->isEmpty())
                    <p class="meeting-empty">Semua sudah ditinjau.</p>
                @else
                    <ul class="item-list">
                        @foreach ($review as $task)
                            <li>
                                <div class="item-row">
                                    <div class="grow">
                                        <div class="cell-title cell-clip" style="max-width: 190px">{{ $task->title }}</div>
                                        <div class="cell-sub">{{ $task->internship->student->name }} ({{ $task->internship->student->nim }})</div>
                                    </div>
                                    <a href="{{ route('tugas.show', $task) }}" class="btn btn-sm">Tinjau</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
