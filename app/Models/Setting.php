<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'description', 'updated_by'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Ambil satu pengaturan; nilai disimpan sebagai JSON. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? ($setting->value['v'] ?? $default) : $default;
    }

    public static function put(string $key, mixed $value, ?string $description = null, ?int $userId = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => ['v' => $value],
                'description' => $description,
                'updated_by' => $userId,
            ], fn ($v) => $v !== null)
        );
    }
}
