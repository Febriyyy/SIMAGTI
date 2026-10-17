<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\AttachTaskFiles;
use App\Actions\Tasks\RemoveTaskAttachment;
use App\Http\Requests\Tasks\StoreAttachmentRequest;
use App\Models\TaskAttachment;
use App\Models\WeeklyTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Lampiran materi task: mentor menambah/menghapus, mahasiswa pemilik dan mentornya mengunduh. */
class TaskAttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, WeeklyTask $task, AttachTaskFiles $attach): RedirectResponse
    {
        $attach->handle($request->user(), $task, $request->attachments());

        return back()->with('status', 'Lampiran ditambahkan.');
    }

    public function destroy(WeeklyTask $task, TaskAttachment $attachment, RemoveTaskAttachment $remove): RedirectResponse
    {
        Gate::authorize('update', $task);

        $remove->handle($attachment);

        return back()->with('status', 'Lampiran dihapus.');
    }

    /** Berkas hanya bisa diambil lewat sini, setelah hak akses atas task-nya dicek. */
    public function show(Request $request, WeeklyTask $task, TaskAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $task);

        $file = $attachment->file;
        abort_unless($file && Storage::disk($file->disk)->exists($file->path), 404);

        $headers = ['Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff'];

        return $file->opensInline() && ! $request->boolean('unduh')
            ? Storage::disk($file->disk)->response($file->path, $file->original_name, $headers)
            : Storage::disk($file->disk)->download($file->path, $file->original_name, $headers);
    }
}
