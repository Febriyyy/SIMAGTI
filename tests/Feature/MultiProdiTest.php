<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\Internship;
use App\Models\LecturerProfile;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\AssessmentStandard;
use App\Support\Campus;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Banyak program studi dalam satu kampus: kaprodi hanya mengurus mahasiswa prodinya,
 * TU dan admin melihat semua, pengaturan sertifikat dan bobot bisa berbeda per prodi.
 * Data tes: Rina, Fajar, Nadia, Gilang, Citra = TIF; Dimas, Salsa, Tari = SI.
 */
class MultiProdiTest extends TestCase
{
    use RefreshDatabase;

    private User $kaprodiTif;

    private User $kaprodiSi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);

        $this->kaprodiTif = $this->kaprodiOf('TIF', 'kaprodi.tif@kp.test');
        $this->kaprodiSi = $this->kaprodiOf('SI', 'kaprodi.si@kp.test');
    }

    private function kaprodiOf(string $code, string $email): User
    {
        $user = User::create(['name' => 'Kaprodi '.$code, 'email' => $email, 'password' => 'rahasia123', 'role' => 'kaprodi', 'is_active' => true]);
        LecturerProfile::create(['user_id' => $user->id, 'study_program_id' => StudyProgram::where('code', $code)->value('id')]);

        return $user->fresh();
    }

    private function internshipOf(string $email): Internship
    {
        return Internship::where('student_id', User::where('email', $email)->value('id'))->latest('id')->firstOrFail();
    }

    public function test_kaprodi_hanya_melihat_mahasiswa_prodinya_tu_melihat_semua(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');   // TIF
        $salsa = $this->internshipOf('salsa@kp.test');      // SI

        $tif = Internship::visibleTo($this->kaprodiTif)->pluck('id');
        $si = Internship::visibleTo($this->kaprodiSi)->pluck('id');
        $this->assertTrue($tif->contains($rina->id));
        $this->assertFalse($tif->contains($salsa->id));
        $this->assertTrue($si->contains($salsa->id));
        $this->assertFalse($si->contains($rina->id));
        $this->assertSame(Internship::count(), $tif->count() + $si->count(), 'Tiap KP milik tepat satu prodi');

        // TU dan kaprodi tanpa prodi melihat semuanya
        $this->assertSame(Internship::count(), Internship::visibleTo(User::where('email', 'tu@kp.test')->first())->count());
        $this->assertSame(Internship::count(), Internship::visibleTo(User::where('email', 'kaprodi@kp.test')->first())->count());

        // Halaman: daftar penempatan dan detail mahasiswa prodi lain
        $this->actingAs($this->kaprodiTif)->get('/penempatan')->assertOk()->assertSee('Rina')->assertDontSee('Salsa Nabila');
        $this->actingAs($this->kaprodiTif)->get("/pantau/{$salsa->id}")->assertForbidden();
        $this->actingAs($this->kaprodiSi)->get("/pantau/{$salsa->id}")->assertOk();
        $this->actingAs($this->kaprodiTif)->get('/rekap')->assertOk()->assertSee('Rina')->assertDontSee('Salsa Nabila');
        $this->actingAs($this->kaprodiTif)->get('/mahasiswa')->assertOk()->assertSee('Rina')->assertDontSee('Dimas');
    }

    public function test_persetujuan_dan_pengesahan_hanya_untuk_prodi_sendiri(): void
    {
        $nadia = $this->internshipOf('nadia@kp.test');      // TIF, KP mandiri
        $this->assertTrue(Gate::forUser($this->kaprodiTif)->allows('reviewAsKaprodi', $nadia));
        $this->assertFalse(Gate::forUser($this->kaprodiSi)->allows('reviewAsKaprodi', $nadia));

        $salsa = $this->internshipOf('salsa@kp.test');      // SI, sudah selesai
        $this->actingAs($this->kaprodiTif)->put("/kaprodi/sidang/{$salsa->id}", ['defended_at' => today()->toDateString()])->assertForbidden();
        $this->actingAs($this->kaprodiTif)->get('/kaprodi/sidang')->assertOk()->assertDontSee('Salsa Nabila');
    }

    public function test_kaprodi_menempatkan_dan_menambah_mahasiswa_hanya_di_prodinya(): void
    {
        $si = StudyProgram::where('code', 'SI')->value('id');
        $this->actingAs($this->kaprodiTif)->post('/mahasiswa', [
            'name' => 'Mahasiswa SI Baru', 'email' => 'baru.si@kp.test', 'nim' => '2310599001',
            'study_program_id' => $si, 'password' => 'rahasia123',
        ])->assertSessionHasErrors('study_program_id');
        $this->actingAs($this->kaprodiSi)->post('/mahasiswa', [
            'name' => 'Mahasiswa SI Baru', 'email' => 'baru.si@kp.test', 'nim' => '2310599001',
            'study_program_id' => $si, 'password' => 'rahasia123',
        ])->assertSessionHasNoErrors();
    }

    public function test_tanda_tangan_dan_bobot_per_prodi(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');      // SI, sertifikat terbit
        $this->actingAs($this->kaprodiSi)->put('/kaprodi/tanda-tangan', ['signer_name' => 'Dr. Kaprodi Sistem Informasi', 'signer_title' => 'Ketua Program Studi'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->kaprodiTif)->put('/kaprodi/tanda-tangan', ['signer_name' => 'Dr. Kaprodi Informatika', 'signer_title' => 'Ketua Program Studi'])
            ->assertSessionHasNoErrors();

        $siId = StudyProgram::where('code', 'SI')->value('id');
        $this->assertSame('Dr. Kaprodi Sistem Informasi', CertificateTemplate::forProgram($siId)->signer_name);
        $this->actingAs($salsa->student)->get("/sertifikat/{$salsa->id}")->assertOk()
            ->assertSee('Dr. Kaprodi Sistem Informasi')->assertDontSee('Dr. Kaprodi Informatika')
            ->assertSee('Program Studi Sistem Informasi');

        // Bobot penilaian: SI punya bobot sendiri, TIF tetap memakai bobot umum
        $before = AssessmentStandard::weights();
        $this->actingAs($this->kaprodiSi)->put('/kaprodi/bobot', ['weights' => ['hard' => 50, 'soft' => 25, 'attitude' => 25]])->assertSessionHasNoErrors();
        $this->assertSame(50.0, AssessmentStandard::weights($siId)['hard']);
        $this->assertSame($before, AssessmentStandard::weights(StudyProgram::where('code', 'TIF')->value('id')));
    }

    public function test_admin_mengatur_identitas_kampus_dan_prodi(): void
    {
        $admin = User::where('email', 'admin@kp.test')->firstOrFail();
        $this->actingAs($admin)->get('/admin/kampus')->assertOk()->assertSee('Program studi')->assertSee('Sistem Informasi');

        $fields = ['university' => 'Universitas Nusantara Raya', 'short' => 'UNR', 'faculty' => 'Fakultas Teknik', 'city' => 'Surabaya',
            'address' => 'Jl. Contoh No. 1', 'letter_code' => 'KP/FT-UNR'];
        $this->actingAs($admin)->put('/admin/kampus', $fields)->assertSessionHasNoErrors();
        Campus::flush();
        $this->assertSame('Universitas Nusantara Raya', Campus::university());
        $this->assertSame('Surabaya', Campus::city());

        $this->actingAs($admin)->post('/admin/kampus/prodi', ['code' => 'ti', 'name' => 'Teknologi Informasi'])->assertSessionHasNoErrors();
        $prodi = StudyProgram::where('code', 'TI')->firstOrFail();
        $this->actingAs($admin)->put("/admin/kampus/prodi/{$prodi->id}", ['name' => 'Teknologi Informasi', 'kaprodi_id' => $this->kaprodiSi->id])->assertSessionHasNoErrors();
        $this->assertSame($prodi->id, $this->kaprodiSi->fresh()->programId());

        // Bukan admin tidak boleh
        $this->actingAs($this->kaprodiTif)->put('/admin/kampus', $fields)->assertForbidden();
    }
}
