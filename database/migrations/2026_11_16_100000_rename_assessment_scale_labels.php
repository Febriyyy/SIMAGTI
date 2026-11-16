<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Label pilihan jawaban penilaian: Cukup -> Cukup baik, Kurang -> Tidak baik, Sangat kurang -> Sangat tidak baik.
 * Hanya menyentuh skala yang sudah tersimpan dan masih memakai label lama; nilainya tidak berubah.
 */
return new class extends Migration
{
    private const RENAME = ['Cukup' => 'Cukup baik', 'Kurang' => 'Tidak baik', 'Sangat kurang' => 'Sangat tidak baik'];

    public function up(): void
    {
        $this->relabel(self::RENAME);
    }

    public function down(): void
    {
        $this->relabel(array_flip(self::RENAME));
    }

    /** @param  array<string, string>  $map */
    private function relabel(array $map): void
    {
        $row = DB::table('settings')->where('key', 'simagti.assessment.scale')->first();
        $value = $row ? json_decode((string) $row->value, true) : null;
        if (! is_array($value['v'] ?? null)) {
            return;
        }

        $value['v'] = array_map(function ($item) use ($map) {
            if (is_array($item) && isset($item['label'], $map[$item['label']])) {
                $item['label'] = $map[$item['label']];
            }

            return $item;
        }, $value['v']);

        DB::table('settings')->where('key', 'simagti.assessment.scale')->update(['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
    }
};
