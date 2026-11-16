<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pertanyaan penilaian bawaan diganti dengan rumusan yang lebih jelas (beserta keterangan singkat), per komponen
 * hard skill (4), soft skill (4), dan attitude (6). Jumlah dan urutan tetap sehingga perhitungan tidak berubah.
 *
 * Hanya diterapkan bila rubrik aktif masih berisi pertanyaan bawaan lama (belum diubah admin).
 * Rubrik yang sudah dipakai penilaian tidak disentuh: dibuat versi baru, penilaian lama tetap memakai versinya.
 */
return new class extends Migration
{
    private const OLD = [
        'Kemampuan kerja', 'Keterampilan kerja', 'Kualitas hasil kerja', 'Kreativitas dan inovasi',
        'Kemampuan berkomunikasi', 'Kerja sama', 'Kerajinan dan inisiatif', 'Percaya diri',
        'Ketepatan waktu dan disiplin', 'Sikap dan prosedur kerja', 'Tanggung jawab', 'Kehadiran', 'Mematuhi aturan perusahaan', 'Penampilan',
    ];

    /** [kategori, pertanyaan, keterangan] dalam urutan yang sama dengan OLD */
    private const NEW = [
        ['hard', 'Penguasaan bidang kerja', 'Memahami dan memakai alat, teknologi, atau metode yang dibutuhkan posisinya.'],
        ['hard', 'Kualitas hasil kerja', 'Hasil kerja akurat, rapi, dan sesuai standar perusahaan.'],
        ['hard', 'Penyelesaian tugas', 'Tugas selesai sesuai target dan tenggat yang disepakati.'],
        ['hard', 'Kreativitas dan pemecahan masalah', 'Menemukan solusi atau cara kerja yang lebih baik saat ada kendala.'],
        ['soft', 'Komunikasi', 'Menyampaikan ide, progres, dan kendala dengan jelas, lisan maupun tulisan.'],
        ['soft', 'Kerja sama tim', 'Bekerja sama dengan baik dan mau membantu rekan kerja.'],
        ['soft', 'Inisiatif', 'Mengambil langkah sendiri tanpa harus selalu diarahkan.'],
        ['soft', 'Kemauan belajar dan menerima masukan', 'Cepat mempelajari hal baru dan terbuka terhadap kritik.'],
        ['attitude', 'Kedisiplinan dan ketepatan waktu', 'Tepat waktu masuk kerja, mengikuti rapat, dan menyerahkan pekerjaan.'],
        ['attitude', 'Kehadiran', 'Hadir sesuai jadwal; izin disampaikan sebelumnya.'],
        ['attitude', 'Tanggung jawab', 'Menuntaskan tugas yang diberikan dan mengakui kesalahan bila ada.'],
        ['attitude', 'Kepatuhan pada aturan perusahaan', 'Mengikuti aturan dan prosedur kerja serta menjaga kerahasiaan perusahaan.'],
        ['attitude', 'Etika dan sopan santun', 'Bersikap sopan dan profesional kepada atasan, rekan, dan klien.'],
        ['attitude', 'Kerapian penampilan', 'Berpakaian rapi dan sesuai ketentuan perusahaan.'],
    ];

    public function up(): void
    {
        $rubric = DB::table('assessment_rubrics')->where('is_active', true)->orderByDesc('id')->first();
        if (! $rubric) {
            return; // Belum ada rubrik: dibuat dari pertanyaan bawaan baru saat pertama dipakai
        }

        $aspects = DB::table('rubric_aspects')->where('rubric_id', $rubric->id)->orderBy('sort_order')->orderBy('id')->get();
        if ($aspects->pluck('name')->all() !== self::OLD) {
            return; // Admin sudah mengubah pertanyaan: jangan ditimpa
        }

        DB::transaction(function () use ($rubric, $aspects) {
            $now = now();
            $used = DB::table('assessments')->where('rubric_id', $rubric->id)->exists();

            if (! $used) {
                foreach ($aspects->values() as $k => $aspect) {
                    DB::table('rubric_aspects')->where('id', $aspect->id)->update(['name' => self::NEW[$k][1], 'description' => self::NEW[$k][2], 'updated_at' => $now]);
                }

                return;
            }

            $newId = DB::table('assessment_rubrics')->insertGetId([
                'name' => $rubric->name, 'is_active' => true, 'created_by' => $rubric->created_by ?? null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($aspects->values() as $k => $aspect) {
                DB::table('rubric_aspects')->insert([
                    'rubric_id' => $newId, 'category' => $aspect->category, 'name' => self::NEW[$k][1], 'description' => self::NEW[$k][2],
                    'kind' => $aspect->kind, 'weight' => $aspect->weight, 'sort_order' => $aspect->sort_order, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            DB::table('assessment_rubrics')->where('id', $rubric->id)->update(['is_active' => false, 'updated_at' => $now]);
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan: versi rubrik lama tetap tersimpan untuk penilaian yang memakainya.
    }
};
