<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\RemoveTaskEvidence;
use App\Models\TaskEvidence;
use App\Models\WeeklyTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Berkas bukti pengerjaan: dibuka oleh mahasiswa pemilik dan mentornya, dihapus oleh mahasiswa. */
class TaskEvidenceController extends Controller
{
    /** Berkas hanya bisa diambil lewat sini, setelah hak akses atas task-nya dicek. */
    public function show(Request $request, WeeklyTask $task, TaskEvidence $evidence): StreamedResponse
    {
        Gate::authorize('view', $task);

        $file = $evidence->file;
        abort_unless($evidence->isFile() && $file && Storage::disk($file->disk)->exists($file->path), 404);

        $headers = ['Content-Type' => $file->mime_type, 'X-Content-Type-Options' => 'nosniff'];

        return $file->opensInline() && ! $request->boolean('unduh')
            ? Storage::disk($file->disk)->response($file->path, $file->original_name, $headers)
            : Storage::disk($file->disk)->download($file->path, $file->original_name, $headers);
    }

    public function destroy(Request $request, WeeklyTask $task, TaskEvidence $evidence, RemoveTaskEvidence $remove): RedirectResponse
    {
        Gate::authorize('submitEvidence', $task);
        abort_unless((int) $evidence->submitted_by === (int) $request->user()->id, 403, 'Bukti ini bukan kiriman Anda.');

        $remove->handle($evidence);

        return back()->with('status', 'Bukti dihapus.');
    }
}
