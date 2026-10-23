<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** API v1: baca-saja, memakai Policy dan scope yang sama dengan web. */
class ApiTest extends TestCase
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

    private function signIn(string $email): static
    {
        Sanctum::actingAs($this->user($email));

        return $this;
    }

    private function internshipOf(string $studentEmail): Internship
    {
        return Internship::where('student_id', $this->user($studentEmail)->id)->latest('id')->firstOrFail();
    }

    // ---------- Token ----------

    public function test_token_diterbitkan_untuk_kredensial_yang_benar_dan_bisa_dipakai(): void
    {
        $response = $this->postJson('/api/v1/auth/token', [
            'email' => 'mahasiswa@kp.test',
            'password' => 'password',
            'device_name' => 'ponsel-uji',
        ])->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('user.role', 'mahasiswa');

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'mahasiswa@kp.test');
    }

    public function test_kata_sandi_salah_atau_akun_nonaktif_tidak_mendapat_token(): void
    {
        $this->postJson('/api/v1/auth/token', ['email' => 'mahasiswa@kp.test', 'password' => 'salah', 'device_name' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->user('dimas@kp.test')->update(['is_active' => false]);

        $this->postJson('/api/v1/auth/token', ['email' => 'dimas@kp.test', 'password' => 'password', 'device_name' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_bisa_dicabut(): void
    {
        $token = $this->postJson('/api/v1/auth/token', ['email' => 'supervisor@kp.test', 'password' => 'password', 'device_name' => 'x'])
            ->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_tanpa_token_dijawab_401_dalam_bentuk_json(): void
    {
        $this->get('/api/v1/internships')->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function test_akun_yang_dinonaktifkan_tidak_bisa_memakai_token_lama(): void
    {
        $user = $this->user('supervisor@kp.test');
        Sanctum::actingAs($user);
        $user->update(['is_active' => false]);

        $this->getJson('/api/v1/me')->assertForbidden();
    }

    // ---------- Penempatan ----------

    public function test_setiap_peran_hanya_melihat_penempatan_yang_menjadi_haknya(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $ids = fn (string $email) => collect($this->signIn($email)->getJson('/api/v1/internships')->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$rina->id], $ids('mahasiswa@kp.test'));

        $supervisorIds = $ids('supervisor@kp.test');
        $this->assertEqualsCanonicalizing(
            Internship::supervisedBy($this->user('supervisor@kp.test'))->pluck('id')->all(),
            $supervisorIds
        );
        $this->assertNotContains($this->internshipOf('dimas@kp.test')->id, $supervisorIds);

        $this->assertSame(Internship::count(), count($ids('kaprodi@kp.test')));
    }

    public function test_daftar_penempatan_bisa_disaring_dan_berisi_progres(): void
    {
        $data = $this->signIn('kaprodi@kp.test')->getJson('/api/v1/internships?status=selesai')->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('selesai', $data[0]['status']);
        $this->assertArrayHasKey('progress_percent', $data[0]);
        $this->assertSame('Salsa Nabila', $data[0]['student']['name']);
    }

    public function test_detail_penempatan_hanya_untuk_yang_berhak(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->signIn('mahasiswa@kp.test')->getJson("/api/v1/internships/{$rina->id}")->assertOk()->assertJsonPath('data.id', $rina->id);
        $this->signIn('supervisor@kp.test')->getJson("/api/v1/internships/{$rina->id}")->assertOk();
        $this->signIn('dimas@kp.test')->getJson("/api/v1/internships/{$rina->id}")->assertForbidden();
        $this->signIn('supervisor2@kp.test')->getJson("/api/v1/internships/{$rina->id}")->assertForbidden();
    }

    public function test_task_penempatan_mengikuti_aturan_akses_yang_sama(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $tasks = $this->signIn('mahasiswa@kp.test')->getJson("/api/v1/internships/{$rina->id}/tasks")->assertOk()->json('data');
        $this->assertNotEmpty($tasks);
        $this->assertArrayHasKey('status_label', $tasks[0]);

        $this->signIn('dimas@kp.test')->getJson("/api/v1/internships/{$rina->id}/tasks")->assertForbidden();
    }

    public function test_api_hanya_membaca(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->signIn('kaprodi@kp.test')->postJson('/api/v1/internships', [])->assertStatus(405);
        $this->signIn('kaprodi@kp.test')->deleteJson("/api/v1/internships/{$rina->id}")->assertStatus(405);
    }

    // ---------- Perusahaan ----------

    public function test_daftar_perusahaan_hanya_untuk_biro_dan_kaprodi(): void
    {
        $this->signIn('biro@kp.test')->getJson('/api/v1/companies')->assertOk()->assertJsonFragment(['name' => 'PT Nusantara Digital']);
        $this->signIn('kaprodi@kp.test')->getJson('/api/v1/companies')->assertOk();
        $this->signIn('mahasiswa@kp.test')->getJson('/api/v1/companies')->assertForbidden();
    }

    public function test_statistik_perusahaan_memuat_jumlah_dan_sebaran_skill_dalam_persen(): void
    {
        $company = Company::where('name', 'PT Nusantara Digital')->firstOrFail();

        $data = $this->signIn('biro@kp.test')->getJson("/api/v1/companies/{$company->id}/stats")->assertOk()->json('data');

        $this->assertSame(2, $data['ongoing_count']);   // Rina dan Fajar
        $this->assertSame(0, $data['finished_count']);
        $this->assertCount(2, $data['students']);

        $skills = collect($data['skills'])->keyBy('name');
        $this->assertSame(100, $skills['Git']['percent']);   // keduanya punya Git
        $this->assertSame(50, $skills['PHP']['percent']);    // hanya Rina
        $this->assertSame(1, $skills['PHP']['students']);
    }

    public function test_statistik_perusahaan_hanya_untuk_staf_perusahaan_itu_sendiri(): void
    {
        $nusantara = Company::where('name', 'PT Nusantara Digital')->firstOrFail();

        $this->signIn('supervisor@kp.test')->getJson("/api/v1/companies/{$nusantara->id}/stats")->assertOk();
        $this->signIn('supervisor2@kp.test')->getJson("/api/v1/companies/{$nusantara->id}/stats")->assertForbidden();
        $this->signIn('mahasiswa@kp.test')->getJson("/api/v1/companies/{$nusantara->id}/stats")->assertForbidden();
    }

    // ---------- Verifikasi sertifikat (publik) ----------

    public function test_sertifikat_dapat_diverifikasi_tanpa_login_dan_tanpa_membuka_nim(): void
    {
        $certificate = Certificate::firstOrFail();

        $this->getJson("/api/v1/certificates/{$certificate->verification_token}")
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('number', $certificate->number)
            ->assertJsonPath('student_name', 'Salsa Nabila')
            ->assertJsonMissingPath('nim');
    }

    public function test_sertifikat_yang_dicabut_tidak_valid(): void
    {
        $certificate = Certificate::firstOrFail();
        $certificate->update(['status' => 'dicabut', 'revoked_at' => now(), 'revoked_reason' => 'Salah data']);

        $this->getJson("/api/v1/certificates/{$certificate->verification_token}")
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('revoked_reason', 'Salah data');
    }

    public function test_token_sertifikat_yang_tidak_dikenal_menghasilkan_404(): void
    {
        $this->getJson('/api/v1/certificates/tidak-ada')->assertNotFound()->assertJsonPath('valid', false);
    }
}
