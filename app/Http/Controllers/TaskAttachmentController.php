<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lampiran materi dari versi awal (saat mentor masih membuat task). Sejak v2 task dibuat mahasiswa,
 * jadi lampiran lama hanya bisa dibuka; berkas baru dikirim sebagai bukti pengerjaan.
 */
class TaskAttachmentController extends Controller
{
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
