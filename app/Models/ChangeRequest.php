<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pengajuan perubahan di tengah KP: perpanjangan periode, pindah tempat, pengunduran diri,
 * atau ganti Company Supervisor. Satu pengajuan aktif per penempatan.
 */
class ChangeRequest extends Model
{
    /** jenis => [label, keterangan] */
    public const TYPES = [
        'extend' => ['Perpanjangan periode', 'Tanggal selesai KP diundur'],
        'transfer' => ['Pindah tempat KP', 'KP ini ditutup, lalu mengajukan tempat baru'],
        'withdraw' => ['Pengunduran diri', 'KP dibatalkan'],
        'replace_supervisor' => ['Ganti Company Supervisor', 'Supervisor resign atau dipindah'],
    ];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'pending' => ['Diproses', 'amber'],
        'approved' => ['Disetujui', 'green'],
        'rejected' => ['Ditolak', 'red'],
    ];

    /** Siapa yang memutuskan berikutnya => label */
    public const STEPS = [
        'supervisor' => 'Company Supervisor',
        'kaprodi' => 'Kaprodi',
        'tu' => 'TU Fakultas',
    ];

    protected $fillable = [
        'internship_id',
        'type',
        'status',
        'step',
        'payload',
        'reason',
        'requested_by',
        'supervisor_note',
        'supervisor_acked_at',
        'decision_note',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'supervisor_acked_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return $this->status === 'pending' && $this->step
            ? 'Menunggu '.(self::STEPS[$this->step] ?? $this->step)
            : (self::STATUSES[$this->status][0] ?? $this->status);
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Ringkasan isi pengajuan dalam satu baris. */
    public function summary(): string
    {
        $p = $this->payload ?? [];

        return match ($this->type) {
            'extend' => 'Selesai menjadi '.Carbon::parse($p['new_end_date'] ?? now())->locale('id')->translatedFormat('j F Y'),
            'replace_supervisor' => 'Supervisor baru: '.($p['supervisor_name'] ?? '–').' ('.($p['supervisor_email'] ?? '–').')',
            'transfer' => 'Tempat baru: '.($p['company_name'] ?? 'akan diajukan setelah disetujui'),
            default => $this->typeLabel(),
        };
    }
}
