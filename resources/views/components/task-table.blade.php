@props(['tasks', 'action' => 'Buka'])
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Hari</th>
                <th>Task</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tasks as $task)
                <tr>
                    <td class="col-day">{{ $task->dayShort() }}</td>
                    <td>
                        <span class="cell-title cell-clip" style="display: inline-block; vertical-align: middle">{{ $task->title }}</span>
                        @if (($task->evidences_count ?? 0) > 0 || ($task->comments_count ?? 0) > 0)
                            <span class="marks">
                                @if (($task->evidences_count ?? 0) > 0)
                                    <span title="Bukti sudah dikirim"><x-icon name="link" :size="15" /><span class="sr-only">Bukti sudah dikirim</span></span>
                                @endif
                                @if (($task->comments_count ?? 0) > 0)
                                    <span title="Ada komentar mentor"><x-icon name="chat" :size="15" /><span class="sr-only">Ada komentar mentor</span></span>
                                @endif
                            </span>
                        @endif
                    </td>
                    <td><x-chip :tone="$task->statusTone()">{{ $task->statusLabel() }}</x-chip></td>
                    <td class="col-action">
                        <a href="{{ route('tugas.show', $task) }}" class="btn btn-sm">{{ $action }}</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
