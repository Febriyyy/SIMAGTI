<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Isi bawaan aplikasi: data master + data realistis (fiktif) dari DataSeeder.
 * Tes otomatis memakai TestingSeeder (data master + DemoSeeder), bukan seeder ini.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MasterSeeder::class,
            DataSeeder::class,
        ]);
    }
}
