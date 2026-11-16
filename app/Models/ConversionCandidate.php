<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Rekomendasi jadi karyawan beserta tawaran kerja resmi dari perusahaan. */
class ConversionCandidate extends Model
{
    /** status => [label, warna chip] */
    public const STATUSES = [
        'direkomendasikan' => ['Direkomendasikan', 'blue'],
        'ditawari' => ['Ditawari kerja', 'amber'],
        'diterima' => ['Diterima', 'green'],
        'ditolak' => ['Menolak', 'gray'],
        'kedaluwarsa' => ['Kedaluwarsa', 'gray'],
    ];

    public const WORK_MODES = ['wfo' => 'Di kantor (WFO)', 'hybrid' => 'Hybrid', 'remote' => 'Jarak jauh (remote)'];

    public const EMPLOYMENT_TYPES = ['tetap' => 'Karyawan tetap', 'kontrak' => 'Karyawan kontrak', 'magang' => 'Magang lanjutan'];

    /** Alasan menolak yang dipilih mahasiswa */
    public const DECLINE_REASONS = [
        'tawaran_lain' => 'Sudah menerima tawaran kerja lain',
        'studi' => 'Melanjutkan studi',
        'tidak_cocok' => 'Lokasi atau waktu kerja tidak cocok',
        'bidang' => 'Ingin berkarier di bidang lain',
        'lainnya' => 'Alasan lain',
    ];

    public const DEFAULT_RESPOND_DAYS = 7;

    protected $fillable = [
        'internship_id', 'status', 'offered_position', 'recommended_by', 'note',
        'unit', 'work_location', 'work_mode', 'employment_type', 'contract_months', 'start_date', 'job_description',
        'compensation', 'hr_contact_name', 'hr_contact_email', 'offer_letter_id',
        'offered_at', 'respond_by', 'responded_at', 'decline_reason', 'decline_note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'respond_by' => 'date',
            'offered_at' => 'datetime',
            'responded_at' => 'datetime',
            'contract_months' => 'integer',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function offerLetter(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'offer_letter_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ConversionHistory::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function workModeLabel(): ?string
    {
        return self::WORK_MODES[$this->work_mode] ?? null;
    }

    /** Mis. "Karyawan kontrak · 12 bulan" */
    public function employmentLabel(): ?string
    {
        $label = self::EMPLOYMENT_TYPES[$this->employment_type] ?? null;

        return $label && $this->employment_type === 'kontrak' && $this->contract_months ? $label.' · '.$this->contract_months.' bulan' : $label;
    }

    public function declineReasonLabel(): ?string
    {
        return self::DECLINE_REASONS[$this->decline_reason] ?? null;
    }

    /** Poin deskripsi pekerjaan (satu baris satu poin). @return list<string> */
    public function jobPoints(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->job_description))
            ->map(fn ($l) => trim(ltrim(trim($l), "-•* \t")))->filter()->values()->all();
    }

    /** Sisa hari sampai batas jawaban (0 = hari ini terakhir; negatif = lewat). */
    public function daysLeft(): ?int
    {
        return $this->respond_by ? (int) now()->startOfDay()->diffInDays($this->respond_by->copy()->startOfDay(), false) : null;
    }

    public function isOpen(): bool
    {
        return $this->status === 'ditawari' && ($this->respond_by === null || $this->daysLeft() >= 0);
    }

    /** Ubah status sekaligus mencatat riwayatnya. */
    public function moveTo(string $status, ?int $userId = null, ?string $note = null): void
    {
        abort_unless(array_key_exists($status, self::STATUSES), 422, 'Status konversi tidak dikenal.');

        $from = $this->status;
        $this->update(['status' => $status]);

        $this->histories()->create([
            'from_status' => $from,
            'to_status' => $status,
            'changed_by' => $userId,
            'note' => $note,
        ]);
    }
}
