<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Logo kampus (pengaturan simagti.campus.logo_file_id) dan logo perusahaan (companies.logo_file_id)
 * yang diunggah manual, dipakai di sertifikat KP. Hanya PNG, JPG, atau WebP (bukan SVG, demi keamanan).
 */
class Logos
{
    public const DISK = 'local';

    public const MAX_KB = 1024;

    public const RULES = ['image', 'mimes:png,jpg,jpeg,webp', 'max:'.self::MAX_KB, 'dimensions:max_width=4000,max_height=4000'];

    public const CAMPUS_KEY = 'simagti.campus.logo_file_id';

    public static function store(UploadedFile $file, User $user, string $folder): StoredFile
    {
        $path = $file->storeAs('logo/'.$folder, Str::uuid().'.'.strtolower($file->extension()), self::DISK);

        return StoredFile::create([
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $user->id,
        ]);
    }

    /** Hapus berkas logo lama dari penyimpanan. */
    public static function discard(?StoredFile $file): void
    {
        if ($file) {
            Storage::disk($file->disk)->delete($file->path);
            $file->forceDelete();
        }
    }

    /** Gambar sebagai data URI (tanda tangan disematkan langsung, tidak lewat tautan publik). */
    public static function dataUri(?StoredFile $file): ?string
    {
        if (! $file || ! in_array($file->mime_type, ['image/png', 'image/jpeg', 'image/webp'], true) || ! Storage::disk($file->disk)->exists($file->path)) {
            return null;
        }

        return 'data:'.$file->mime_type.';base64,'.base64_encode(Storage::disk($file->disk)->get($file->path));
    }

    public static function campus(): ?StoredFile
    {
        $id = Setting::get(self::CAMPUS_KEY);

        return $id ? StoredFile::find($id) : null;
    }

    public static function setCampus(?StoredFile $file, User $user): void
    {
        $old = self::campus();
        Setting::put(self::CAMPUS_KEY, $file?->id, 'Logo kampus untuk sertifikat KP', $user->id);
        if ($old && $old->id !== $file?->id) {
            self::discard($old);
        }
    }
}
