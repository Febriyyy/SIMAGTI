<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\User;

/** Penempatan yang sedang dipakai mahasiswa: yang berjalan, atau yang terakhir (selesai) untuk dibaca. */
class ActiveInternship
{
    public static function for(User $student, array $with = []): ?Internship
    {
        return Internship::with($with)
            ->where('student_id', $student->id)
            ->whereIn('status', ['berjalan', 'selesai'])
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 ELSE 1 END")
            ->latest('start_date')
            ->first();
    }
}
