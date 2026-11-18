<?php

namespace App\Models;

use App\Support\LinkLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Proyek yang pernah dibuat mahasiswa (di luar task magang), ditampilkan di portofolio. */
class PortfolioProject extends Model
{
    protected $fillable = ['portfolio_id', 'title', 'role', 'description', 'tech', 'url', 'links', 'year', 'image_file_id', 'position'];

    protected function casts(): array
    {
        return ['links' => 'array'];
    }

    /** Tautan proyek (repo, demo, desain, video, dokumen) dengan label otomatis. */
    public function linkList(): array
    {
        return LinkLabel::list($this->links ?: ($this->url ? [$this->url] : []));
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'image_file_id');
    }

    /** Teknologi sebagai daftar (disimpan dipisah koma). */
    public function techList(): array
    {
        return collect(explode(',', (string) $this->tech))->map(fn ($t) => trim($t))->filter()->values()->all();
    }

    public function host(): ?string
    {
        return $this->url ? preg_replace('/^www\./', '', (string) parse_url($this->url, PHP_URL_HOST)) : null;
    }
}
