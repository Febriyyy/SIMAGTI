<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentFolder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'created_by',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DocumentFolder::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
