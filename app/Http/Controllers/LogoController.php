<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\StoredFile;
use App\Support\Logos;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan logo kampus dan logo perusahaan (bukan data pribadi), dipakai sertifikat KP
 * dan halaman verifikasi sertifikat yang bisa dibuka tanpa login.
 */
class LogoController extends Controller
{
    public function campus(): StreamedResponse
    {
        return $this->stream(Logos::campus());
    }

    public function faculty(): StreamedResponse
    {
        return $this->stream(Logos::faculty());
    }

    public function company(Company $company): StreamedResponse
    {
        return $this->stream($company->logo);
    }

    private function stream(?StoredFile $file): StreamedResponse
    {
        abort_unless($file && in_array($file->mime_type, ['image/png', 'image/jpeg', 'image/webp'], true), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
        ], 'inline');
    }
}
