<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Alur KP mandiri: pengajuan → verifikasi biro → persetujuan kaprodi → undangan mentor. */
class ApplicationFlowTest extends TestCase
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

    private function newStudent(string $nim = '2210519999'): User
    {
        return User::create([
            'name' => 'Mahasiswa Baru',
            'email' => "baru{$nim}@kp.test",
            'password' => 'password',
            'role' => 'mahasiswa',
            'nim' => $nim,
            'is_active' => true,
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'company_name' => 'PT Contoh Baru',
            'industry' => 'Fintech',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'mentor_email' => 'mentor.baru@contoh.test',
            'supervisor_email' => 'divisi.baru@contoh.test',
        ], $override);
    }

    private function submit(User $student, array $override = []): Internship
    {
        $this->actingAs($student)->post('/pengajuan-kp', $this->payload($override))->assertRedirect('/pengajuan-kp');

        return Internship::where('student_id', $student->id)->latest('id')->firstOrFail();
    }

    public function test_mahasiswa_mengajukan_kp_mandiri(): void
    {
        $student = $this->newStudent();
        $internship = $this->submit($student);

        $this->assertSame('mandiri', $internship->track);
        $this->assertSame('diajukan', $internship->status);
        $this->assertDatabaseHas('companies', ['name' => 'PT Contoh Baru', 'status' => 'mandiri_menunggu', 'created_by' => $student->id]);
        $this->assertDatabaseHas('internship_approvals', ['internship_id' => $internship->id, 'step' => 'biro_verification', 'status' => 'pending']);
        $this->assertDatabaseHas('internship_approvals', ['internship_id' => $internship->id, 'step' => 'kaprodi_approval', 'status' => 'pending']);
        $this->assertDatabaseHas('invitations', ['internship_id' => $internship->id, 'email' => 'mentor.baru@contoh.test', 'role' => 'mentor']);
        $this->assertDatabaseHas('invitations', ['internship_id' => $internship->id, 'email' => 'divisi.baru@contoh.test', 'role' => 'ketua_divisi']);
    }

    public function test_mahasiswa_dengan_penempatan_aktif_tidak_bisa_mengajukan(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->post('/pengajuan-kp', $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('companies', ['name' => 'PT Contoh Baru']);
    }

    public function test_tanggal_selesai_harus_setelah_tanggal_mulai(): void
    {
        $this->actingAs($this->newStudent())
            ->post('/pengajuan-kp', $this->payload(['end_date' => now()->addDays(5)->toDateString()]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_hanya_mahasiswa_yang_membuka_halaman_pengajuan(): void
    {
        $this->actingAs($this->user('mentor@kp.test'))->get('/pengajuan-kp')->assertForbidden();
        $this->actingAs($this->newStudent())->get('/pengajuan-kp')->assertOk();
    }

    public function test_email_staf_yang_sudah_terdaftar_langsung_dipasang_tanpa_undangan(): void
    {
        $internship = $this->submit($this->newStudent(), ['mentor_email' => 'mentor@kp.test', 'supervisor_email' => null]);

        $this->assertTrue($internship->isMentoredBy($this->user('mentor@kp.test')));
        $this->assertSame(0, Invitation::where('internship_id', $internship->id)->count());
    }

    public function test_email_milik_peran_lain_ditolak_dan_tidak_ada_data_tersisa(): void
    {
        $this->actingAs($this->newStudent())
            ->post('/pengajuan-kp', $this->payload(['mentor_email' => 'kaprodi@kp.test']))
            ->assertSessionHasErrors('mentor_email');

        $this->assertDatabaseMissing('companies', ['name' => 'PT Contoh Baru']);
    }

    public function test_alur_lengkap_dari_pengajuan_sampai_penempatan_aktif(): void
    {
        $student = $this->newStudent();
        $internship = $this->submit($student);
        $dospem = $this->user('dospem@kp.test');

        // Biro memverifikasi perusahaan
        $this->actingAs($this->user('biro@kp.test'))
            ->patch("/biro/verifikasi/{$internship->id}", ['decision' => 'approve'])
            ->assertRedirect('/biro/verifikasi');

        $this->assertDatabaseHas('companies', ['id' => $internship->company_id, 'status' => 'mandiri_terverifikasi']);
        $this->assertDatabaseHas('internship_approvals', ['internship_id' => $internship->id, 'step' => 'biro_verification', 'status' => 'approved']);
        $this->assertSame('diajukan', $internship->fresh()->status);

        // Kaprodi menyetujui dan menunjuk dosen pembimbing
        $this->actingAs($this->user('kaprodi@kp.test'))
            ->patch("/kaprodi/persetujuan/{$internship->id}", ['decision' => 'approve', 'lecturer_id' => $dospem->id])
            ->assertRedirect('/kaprodi/persetujuan');

        $fresh = $internship->fresh();
        $this->assertSame('berjalan', $fresh->status);
        $this->assertSame($dospem->id, $fresh->lecturer_id);
        $this->assertNull($fresh->locked_at);

        // Setelah aktif, mahasiswa tidak bisa mengajukan lagi
        $this->actingAs($student)->post('/pengajuan-kp', $this->payload(['company_name' => 'PT Lain']))->assertForbidden();
    }

    public function test_kaprodi_tidak_bisa_menyetujui_sebelum_biro_memverifikasi(): void
    {
        $internship = $this->submit($this->newStudent());

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->patch("/kaprodi/persetujuan/{$internship->id}", ['decision' => 'approve', 'lecturer_id' => $this->user('dospem@kp.test')->id])
            ->assertSessionHasErrors('aturan');

        $this->assertSame('diajukan', $internship->fresh()->status);
    }

    public function test_pengajuan_yang_sudah_diverifikasi_tidak_bisa_diverifikasi_dua_kali(): void
    {
        $internship = $this->submit($this->newStudent());
        $biro = $this->user('biro@kp.test');

        $this->actingAs($biro)->patch("/biro/verifikasi/{$internship->id}", ['decision' => 'approve']);
        $this->actingAs($biro)
            ->patch("/biro/verifikasi/{$internship->id}", ['decision' => 'reject', 'note' => 'Salah klik'])
            ->assertSessionHasErrors('aturan');

        $this->assertSame('diajukan', $internship->fresh()->status);
    }

    public function test_penolakan_wajib_disertai_alasan(): void
    {
        $internship = $this->submit($this->newStudent());

        $this->actingAs($this->user('biro@kp.test'))
            ->patch("/biro/verifikasi/{$internship->id}", ['decision' => 'reject'])
            ->assertSessionHasErrors('note');
    }

    public function test_pengajuan_ditolak_membebaskan_mahasiswa_untuk_mengajukan_ulang(): void
    {
        $student = $this->newStudent();
        $internship = $this->submit($student);

        $this->actingAs($this->user('biro@kp.test'))
            ->patch("/biro/verifikasi/{$internship->id}", ['decision' => 'reject', 'note' => 'Perusahaan tidak ditemukan']);

        $fresh = $internship->fresh();
        $this->assertSame('ditolak', $fresh->status);
        $this->assertNull($fresh->active_student_id);

        $this->submit($student, ['company_name' => 'PT Pengganti', 'mentor_email' => null, 'supervisor_email' => null]);
        $this->assertDatabaseHas('companies', ['name' => 'PT Pengganti']);
    }

    public function test_peran_lain_tidak_bisa_memproses_pengajuan(): void
    {
        $internship = $this->submit($this->newStudent());

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->patch("/biro/verifikasi/{$internship->id}", ['decision' => 'approve'])
            ->assertForbidden();

        $this->actingAs($this->user('biro@kp.test'))
            ->patch("/kaprodi/persetujuan/{$internship->id}", ['decision' => 'reject', 'note' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->user('mentor@kp.test'))->get('/biro/verifikasi')->assertForbidden();
    }

    public function test_antrean_kaprodi_hanya_berisi_pengajuan_yang_sudah_diverifikasi_biro(): void
    {
        $internship = $this->submit($this->newStudent(), ['company_name' => 'PT Antrean Uji']);
        $kaprodi = $this->user('kaprodi@kp.test');

        $this->actingAs($kaprodi)->get('/kaprodi/persetujuan')->assertOk()->assertDontSee('PT Antrean Uji');

        $this->actingAs($this->user('biro@kp.test'))->patch("/biro/verifikasi/{$internship->id}", ['decision' => 'approve']);

        $this->actingAs($kaprodi)->get('/kaprodi/persetujuan')->assertOk()->assertSee('PT Antrean Uji');
    }

    public function test_mentor_mengaktifkan_akun_lewat_undangan(): void
    {
        $internship = $this->submit($this->newStudent());
        $invitation = Invitation::where('internship_id', $internship->id)->where('role', 'mentor')->firstOrFail();

        $this->get("/undangan/{$invitation->token}")->assertOk()->assertSee('Aktivasi akun');

        $this->post("/undangan/{$invitation->token}", [
            'name' => 'Mentor Baru',
            'password' => 'sandi-rahasia-1',
            'password_confirmation' => 'sandi-rahasia-1',
        ])->assertRedirect('/login');

        $mentor = User::where('email', 'mentor.baru@contoh.test')->firstOrFail();
        $this->assertSame('mentor', $mentor->role);
        $this->assertTrue($internship->fresh()->isMentoredBy($mentor));
        $this->assertDatabaseHas('company_staff_profiles', ['user_id' => $mentor->id, 'company_id' => $internship->company_id]);
        $this->assertNotNull($invitation->fresh()->accepted_at);

        $this->post('/login', ['email' => 'mentor.baru@contoh.test', 'password' => 'sandi-rahasia-1'])->assertRedirect('/dashboard');
    }

    public function test_undangan_tidak_bisa_dipakai_dua_kali(): void
    {
        $internship = $this->submit($this->newStudent());
        $invitation = Invitation::where('internship_id', $internship->id)->where('role', 'mentor')->firstOrFail();
        $data = ['name' => 'Mentor Baru', 'password' => 'sandi-rahasia-1', 'password_confirmation' => 'sandi-rahasia-1'];

        $this->post("/undangan/{$invitation->token}", $data)->assertRedirect('/login');
        $this->post("/undangan/{$invitation->token}", $data)->assertSessionHasErrors('aturan');

        $this->assertSame(1, User::where('email', 'mentor.baru@contoh.test')->count());
    }

    public function test_undangan_kedaluwarsa_tidak_bisa_dipakai(): void
    {
        $internship = $this->submit($this->newStudent());
        $invitation = Invitation::where('internship_id', $internship->id)->where('role', 'mentor')->firstOrFail();
        $invitation->update(['expires_at' => now()->subDay()]);

        $this->get("/undangan/{$invitation->token}")->assertOk()->assertSee('Undangan tidak berlaku');

        $this->post("/undangan/{$invitation->token}", [
            'name' => 'Mentor Baru',
            'password' => 'sandi-rahasia-1',
            'password_confirmation' => 'sandi-rahasia-1',
        ])->assertSessionHasErrors('aturan');

        $this->assertDatabaseMissing('users', ['email' => 'mentor.baru@contoh.test']);
    }

    public function test_token_undangan_yang_salah_menghasilkan_404(): void
    {
        $this->get('/undangan/token-tidak-ada')->assertNotFound();
    }

    public function test_kata_sandi_undangan_minimal_8_karakter_dan_harus_cocok(): void
    {
        $internship = $this->submit($this->newStudent());
        $invitation = Invitation::where('internship_id', $internship->id)->where('role', 'mentor')->firstOrFail();

        $this->post("/undangan/{$invitation->token}", ['name' => 'X', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->post("/undangan/{$invitation->token}", ['name' => 'X', 'password' => 'sandi-rahasia-1', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors('password');
    }
}
