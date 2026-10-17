<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Data master: perusahaan (biro), mahasiswa dan penempatan (kaprodi). */
class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function freeStudent(): User
    {
        return User::create([
            'name' => 'Mahasiswa Lowong',
            'email' => 'lowong@kp.test',
            'password' => 'password',
            'role' => 'mahasiswa',
            'nim' => '2210518888',
            'is_active' => true,
        ]);
    }

    private function nusantara(): Company
    {
        return Company::where('name', 'PT Nusantara Digital')->firstOrFail();
    }

    private function placementPayload(User $student, array $override = []): array
    {
        return array_merge([
            'student_id' => $student->id,
            'company_id' => $this->nusantara()->id,
            'mentor_id' => $this->user('mentor@kp.test')->id,
            'supervisor_id' => $this->user('divisi@kp.test')->id,
            'lecturer_id' => $this->user('dospem@kp.test')->id,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
        ], $override);
    }

    // ---------- Perusahaan ----------

    public function test_biro_dan_kaprodi_melihat_daftar_perusahaan_peran_lain_tidak(): void
    {
        $this->actingAs($this->user('biro@kp.test'))->get('/perusahaan')->assertOk()->assertSee('PT Nusantara Digital');
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/perusahaan')->assertOk();
        $this->actingAs($this->user('mentor@kp.test'))->get('/perusahaan')->assertForbidden();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/perusahaan')->assertForbidden();
    }

    public function test_biro_menambah_perusahaan_beserta_divisinya(): void
    {
        $this->actingAs($this->user('biro@kp.test'))
            ->post('/perusahaan', [
                'name' => 'PT Mitra Baru',
                'industry' => 'Logistik',
                'city' => 'Bandung',
                'divisions' => "IT\n  Finance  \nIT\n\n",
            ])
            ->assertRedirect('/perusahaan');

        $company = Company::where('name', 'PT Mitra Baru')->firstOrFail();
        $this->assertSame('mitra', $company->status);
        $this->assertEqualsCanonicalizing(['IT', 'Finance'], $company->divisions()->pluck('name')->all());
    }

    public function test_nama_perusahaan_tidak_boleh_kembar(): void
    {
        $this->actingAs($this->user('biro@kp.test'))
            ->post('/perusahaan', ['name' => 'PT Nusantara Digital'])
            ->assertSessionHasErrors('name');
    }

    public function test_biro_mengubah_perusahaan_tanpa_bentrok_dengan_namanya_sendiri(): void
    {
        $company = $this->nusantara();

        $this->actingAs($this->user('biro@kp.test'))
            ->patch("/perusahaan/{$company->id}", ['name' => 'PT Nusantara Digital', 'city' => 'Jakarta'])
            ->assertRedirect('/perusahaan');

        $this->assertSame('Jakarta', $company->fresh()->city);
    }

    public function test_kaprodi_hanya_boleh_melihat_perusahaan_bukan_mengubahnya(): void
    {
        $company = $this->nusantara();
        $kaprodi = $this->user('kaprodi@kp.test');

        $this->actingAs($kaprodi)->get('/perusahaan/tambah')->assertForbidden();
        $this->actingAs($kaprodi)->post('/perusahaan', ['name' => 'PT Tidak Boleh'])->assertForbidden();
        $this->actingAs($kaprodi)->patch("/perusahaan/{$company->id}", ['name' => 'Diubah'])->assertForbidden();

        $this->assertDatabaseMissing('companies', ['name' => 'PT Tidak Boleh']);
        $this->assertSame('PT Nusantara Digital', $company->fresh()->name);
    }

    // ---------- Mahasiswa ----------

    public function test_kaprodi_menambah_mahasiswa_beserta_profilnya(): void
    {
        $program = StudyProgram::firstOrFail();

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->post('/mahasiswa', [
                'name' => 'Budi Santoso',
                'email' => 'Budi@Kampus.test',
                'nim' => '2210517777',
                'study_program_id' => $program->id,
                'entry_year' => 2022,
                'gpa' => 3.4,
                'password' => 'sandi-awal-1',
            ])
            ->assertRedirect('/mahasiswa');

        $student = User::where('nim', '2210517777')->firstOrFail();
        $this->assertSame('mahasiswa', $student->role);
        $this->assertSame('budi@kampus.test', $student->email);
        $this->assertTrue(Hash::check('sandi-awal-1', $student->password));
        $this->assertDatabaseHas('student_profiles', ['user_id' => $student->id, 'study_program_id' => $program->id, 'entry_year' => 2022]);
    }

    public function test_nim_dan_email_mahasiswa_harus_unik(): void
    {
        $program = StudyProgram::firstOrFail();
        $kaprodi = $this->user('kaprodi@kp.test');
        $base = ['name' => 'X', 'study_program_id' => $program->id, 'password' => 'sandi-awal-1'];

        $this->actingAs($kaprodi)
            ->post('/mahasiswa', $base + ['email' => 'baru@kampus.test', 'nim' => '2210511012'])
            ->assertSessionHasErrors('nim');

        $this->actingAs($kaprodi)
            ->post('/mahasiswa', $base + ['email' => 'mahasiswa@kp.test', 'nim' => '2210516666'])
            ->assertSessionHasErrors('email');
    }

    public function test_hanya_kaprodi_yang_mengelola_mahasiswa(): void
    {
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/mahasiswa')->assertOk()->assertSee('Rina Putri');
        $this->actingAs($this->user('biro@kp.test'))->get('/mahasiswa')->assertForbidden();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/mahasiswa')->assertForbidden();
    }

    // ---------- Penempatan ----------

    public function test_kaprodi_membuat_penempatan_langsung_aktif_beserta_stafnya(): void
    {
        $student = $this->freeStudent();

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->post('/penempatan', $this->placementPayload($student))
            ->assertRedirect('/penempatan');

        $internship = Internship::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('lowongan', $internship->track);
        $this->assertSame('berjalan', $internship->status);
        $this->assertSame($this->user('dospem@kp.test')->id, $internship->lecturer_id);
        $this->assertTrue($internship->isMentoredBy($this->user('mentor@kp.test')));
        $this->assertTrue($internship->isSupervisedBy($this->user('divisi@kp.test')));
        $this->assertDatabaseHas('internship_status_histories', ['internship_id' => $internship->id, 'to_status' => 'berjalan']);
    }

    public function test_mahasiswa_dengan_penempatan_aktif_tidak_bisa_ditempatkan_lagi(): void
    {
        $rina = $this->user('mahasiswa@kp.test');

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->post('/penempatan', $this->placementPayload($rina))
            ->assertSessionHasErrors('student_id');
    }

    public function test_mentor_harus_staf_dari_perusahaan_yang_dipilih(): void
    {
        $student = $this->freeStudent();

        // mentor2 bekerja di PT Data Prima, bukan PT Nusantara Digital
        $this->actingAs($this->user('kaprodi@kp.test'))
            ->post('/penempatan', $this->placementPayload($student, ['mentor_id' => $this->user('mentor2@kp.test')->id]))
            ->assertSessionHasErrors('mentor_id');

        $this->assertDatabaseMissing('internships', ['student_id' => $student->id]);
    }

    public function test_pengguna_dengan_peran_salah_ditolak_sebagai_mentor_atau_dosen(): void
    {
        $student = $this->freeStudent();

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->post('/penempatan', $this->placementPayload($student, ['lecturer_id' => $this->user('mentor@kp.test')->id]))
            ->assertSessionHasErrors('lecturer_id');
    }

    public function test_tanggal_selesai_penempatan_harus_setelah_tanggal_mulai(): void
    {
        $this->actingAs($this->user('kaprodi@kp.test'))
            ->post('/penempatan', $this->placementPayload($this->freeStudent(), ['end_date' => now()->toDateString()]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_biro_boleh_melihat_penempatan_tetapi_tidak_membuatnya(): void
    {
        $biro = $this->user('biro@kp.test');
        $student = $this->freeStudent();

        $this->actingAs($biro)->get('/penempatan')->assertOk();
        $this->actingAs($biro)->get('/penempatan/tambah')->assertForbidden();
        $this->actingAs($biro)->post('/penempatan', $this->placementPayload($student))->assertForbidden();

        $this->assertDatabaseMissing('internships', ['student_id' => $student->id]);
    }

    public function test_daftar_penempatan_bisa_disaring_menurut_status(): void
    {
        $kaprodi = $this->user('kaprodi@kp.test');

        $this->actingAs($kaprodi)->get('/penempatan?status=selesai')->assertOk()->assertSee('Salsa Nabila')->assertDontSee('Rina Putri');
        $this->actingAs($kaprodi)->get('/penempatan?status=tidak-ada')->assertOk();
    }

    public function test_halaman_form_penempatan_terbuka_untuk_kaprodi(): void
    {
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/penempatan/tambah')->assertOk()->assertSee('Tambah penempatan');
    }
}
