<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data khusus tes otomatis: data master + DemoSeeder (akun *@kp.test).
 * Tidak dipakai untuk aplikasi; isinya sengaja kecil dan tetap supaya hasil tes bisa diprediksi.
 */
class TestingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([MasterSeeder::class, DemoSeeder::class]);
    }
}
