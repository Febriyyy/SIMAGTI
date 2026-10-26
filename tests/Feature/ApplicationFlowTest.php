<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\Invitation;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur pengajuan KP (satu jenis KP): mahasiswa mengajukan → TU Fakultas memverifikasi perusahaan →
 * kaprodi menyetujui → TU Fakultas mengaktifkan Company Supervisor → supervisor menerima undangan.
 */
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

    private function newStudent(string $nim = '2310519999', ?int $semester = null): User
    {
        $student = User::create([
            'name' => 'Mahasiswa Baru '.$nim,
            'email' => "baru{$nim}@kp.test",
            'password' => 'password',
            'role' => 'mahasiswa',
            'nim' => $nim,
            'is_active' => true,
        ]);

        if ($semester !== null) {
            // semester = (tahun ini - angkatan) * 2 + (1 jika bulan >= September)
            $entry = now()->year - intdiv($semester - (now()->month >= 9 ? 1 : 0), 2);
            StudentProfile::create(['user_id' => $student->id, 'study_program_id' => StudyProgram::first()->id, 'entry_year' => $entry]);
        }

        return $student;
    }

    private function magang(array $override = []): array
    {
        return array_merge([
            'company_name' => 'PT Contoh Baru',
            'industry' => 'Keuangan',
            'city' => 'Bandung',
            'address' => 'Jl. Contoh No. 1',
            'division_name' => 'Engineering',
            'position' => 'Backend Developer Intern',
            'supervisor_name' => 'Bima Saputra',
            'supervisor_title' => 'Engineering Manager',
            'supervisor_email' => 'Bima@Contoh.test',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
        ], $override);
    }

    private function submit(User $student, array $payload): Internship
    {
        $this->actingAs($student)->post('/status-kp', $payload)->assertRedirect('/status-kp')->assertSessionHasNoErrors();

        return Internship::where('student_id', $student->id)->latest('id')->firstOrFail();
    }

    /** TU Fakultas memverifikasi lalu kaprodi menyetujui: penempatan berjalan. */
    private function approve(Internship $internship): Internship
    {
        $this->actingAs($this->user('tu@kp.test'))->patch("/tu/verifikasi/{$internship->id}", ['decision' => 'approve']);
        $this->actingAs($this->user('kaprodi@kp.test'))
            ->patch("/kaprodi/persetujuan/{$internship->id}", ['decision' => 'approve', 'lecturer_id' => $this->user('dospem@kp.test')->id])
            ->assertSessionHasNoErrors();

        return $internship->fresh();
    }

    // ---------- Pengajuan ----------

    public function test_mahasiswa_mengajukan_magang_dengan_data_company_supervisor(): void
    {
        $student = $this->newStudent();
        $internship = $this->submit($student, $this->magang());

        $this->assertSame(['mandiri', 'diajukan'], [$internship->track, $internship->status]);
        $this->assertSame('Keuangan', $internship->company->industry);
        $this->assertSame('Backend Developer Intern', $internship->position);
        $this->assertSame('bima@contoh.test', $internship->supervisor_email, 'Email disimpan huruf kecil');
        $this->assertSame('Engineering', $internship->division->name);
        $this->assertDatabaseHas('companies', ['name' => 'PT Contoh Baru', 'status' => 'mandiri_menunggu', 'created_by' => $student->id]);
        $this->assertDatabaseHas('internship_approvals', ['internship_id' => $internship->id, 'step' => 'tu_verification', 'status' => 'pending']);
        $this->assertDatabaseHas('internship_approvals', ['internship_id' => $internship->id, 'step' => 'kaprodi_approval', 'status' => 'pending']);
        // Undangan tidak lagi dibuat otomatis: TU Fakultas yang mengaktifkan supervisor.
        $this->assertSame(0, Invitation::where('internship_id', $internship->id)->count());
    }

    public function test_bidang_usaha_lainnya_diisi_sendiri_dan_website_opsional(): void
    {
        $internship = $this->submit($this->newStudent(), $this->magang([
            'company_name' => 'PT Sehat Sentosa', 'industry' => 'lainnya', 'industry_other' => 'Kesehatan', 'website' => 'sehatsentosa.co.id',
        ]));
        $this->assertSame('Kesehatan', $internship->company->industry);
        $this->assertSame('https://sehatsentosa.co.id', $internship->company->website);

        $other = $this->newStudent('2310518888');
        $this->actingAs($other)->post('/status-kp', $this->magang(['company_name' => 'PT Tanpa Bidang', 'industry' => 'lainnya', 'industry_other' => '']))
            ->assertSessionHasErrors('industry');
        $this->submit($other, $this->magang(['company_name' => 'PT Tanpa Web', 'website' => '']));
        $this->assertDatabaseHas('companies', ['name' => 'PT Tanpa Web', 'website' => null]);
    }

    public function test_data_wajib_diisi(): void
    {
        $student = $this->newStudent();

        $this->actingAs($student)->post('/status-kp', $this->magang(['supervisor_email' => '', 'position' => '', 'company_name' => '', 'industry' => '']))
            ->assertSessionHasErrors(['supervisor_email', 'position', 'company_name', 'industry']);
    }

    public function test_syarat_semester_minimal(): void
    {
        $this->actingAs($this->newStudent('2310510001', semester: 3))
            ->post('/status-kp', $this->magang())
            ->assertSessionHasErrors('start_date');

        $this->submit($this->newStudent('2310510003', semester: 6), $this->magang());
        $this->assertDatabaseMissing('companies', ['created_by' => $this->user('baru2310510001@kp.test')->id]);
    }

    public function test_perusahaan_mitra_dipakai_ulang_dan_tidak_diverifikasi_ulang(): void
    {
        $internship = $this->submit($this->newStudent(), $this->magang(['company_name' => 'pt nusantara digital']));
        $nusantara = Company::where('name', 'PT Nusantara Digital')->firstOrFail();

        $this->assertSame($nusantara->id, $internship->company_id);
        $this->assertSame(1, Company::whereRaw('LOWER(name) = ?', ['pt nusantara digital'])->count());
        $this->assertDatabaseHas('internship_approvals', ['internship_id' => $internship->id, 'step' => 'tu_verification', 'status' => 'approved']);
        $this->assertTrue(Internship::awaitingKaprodi()->whereKey($internship->id)->exists());
    }

    public function test_mahasiswa_dengan_kp_aktif_tidak_bisa_mengajukan(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->post('/status-kp', $this->magang())->assertForbidden();
        $this->assertDatabaseMissing('companies', ['name' => 'PT Contoh Baru']);
    }

    public function test_tanggal_selesai_harus_setelah_tanggal_mulai(): void
    {
        $this->actingAs($this->newStudent())
            ->post('/status-kp', $this->magang(['end_date' => now()->addDays(5)->toDateString()]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_halaman_status_kp_hanya_untuk_mahasiswa_dan_menampilkan_langkah(): void
    {
        $this->actingAs($this->user('supervisor@kp.test'))->get('/status-kp')->assertForbidden();
        $this->actingAs($this->newStudent())->get('/status-kp')->assertOk()->assertSee('Ajukan KP')->assertSee('Lainnya…')->assertDontSee('MBKM')->assertDontSee('Seminar');

        $this->actingAs($this->user('nadia@kp.test'))->get('/status-kp')->assertOk()
            ->assertSee('Verifikasi perusahaan')
            ->assertSee('Persetujuan dan dosen pembimbing')
            ->assertSee('Aktivasi Company Supervisor')
            ->assertDontSee('Ajukan ulang');
    }

    // ---------- TU Fakultas dan kaprodi ----------

    public function test_alur_lengkap_sampai_antrean_tu_fakultas(): void
    {
        $internship = $this->approve($this->submit($this->newStudent(), $this->magang()));

        $this->assertSame('berjalan', $internship->status);
        $this->assertSame($this->user('dospem@kp.test')->id, $internship->lecturer_id);
        $this->assertDatabaseHas('companies', ['id' => $internship->company_id, 'status' => 'mandiri_terverifikasi']);
        $this->assertTrue(Internship::awaitingSupervisor()->whereKey($internship->id)->exists());

        $this->actingAs($this->user('tu@kp.test'))->get('/tu/aktivasi')->assertOk()->assertSee('bima@contoh.test')->assertSee('Bima Saputra');
    }

    public function test_kaprodi_tidak_bisa_menyetujui_sebelum_tu_memverifikasi(): void
    {
        $internship = $this->submit($this->newStudent(), $this->magang());

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->patch("/kaprodi/persetujuan/{$internship->id}", ['decision' => 'approve', 'lecturer_id' => $this->user('dospem@kp.test')->id])
            ->assertSessionHasErrors('aturan');

        $this->assertSame('diajukan', $internship->fresh()->status);
    }

    public function test_pengajuan_yang_sudah_diverifikasi_tidak_bisa_diverifikasi_dua_kali(): void
    {
        $internship = $this->submit($this->newStudent(), $this->magang());
        $tu = $this->user('tu@kp.test');

        $this->actingAs($tu)->patch("/tu/verifikasi/{$internship->id}", ['decision' => 'approve']);
        $this->actingAs($tu)
            ->patch("/tu/verifikasi/{$internship->id}", ['decision' => 'reject', 'note' => 'Salah klik'])
            ->assertSessionHasErrors('aturan');

        $this->assertSame('diajukan', $internship->fresh()->status);
    }

    public function test_penolakan_wajib_disertai_alasan_dan_membebaskan_mahasiswa(): void
    {
        $student = $this->newStudent();
        $internship = $this->submit($student, $this->magang());
        $tu = $this->user('tu@kp.test');

        $this->actingAs($tu)->patch("/tu/verifikasi/{$internship->id}", ['decision' => 'reject'])->assertSessionHasErrors('note');
        $this->actingAs($tu)->patch("/tu/verifikasi/{$internship->id}", ['decision' => 'reject', 'note' => 'Perusahaan tidak ditemukan']);

        $fresh = $internship->fresh();
        $this->assertSame('ditolak', $fresh->status);
        $this->assertNull($fresh->active_student_id);

        $this->actingAs($student)->get('/status-kp')->assertOk()->assertSee('Ajukan ulang')->assertSee('Perusahaan tidak ditemukan');
        $this->submit($student, $this->magang(['company_name' => 'PT Pengganti']));
    }

    public function test_peran_lain_tidak_bisa_memproses_pengajuan(): void
    {
        $internship = $this->submit($this->newStudent(), $this->magang());

        $this->actingAs($this->user('kaprodi@kp.test'))->patch("/tu/verifikasi/{$internship->id}", ['decision' => 'approve'])->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->patch("/kaprodi/persetujuan/{$internship->id}", ['decision' => 'reject', 'note' => 'x'])->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->get('/tu/verifikasi')->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get('/kaprodi/persetujuan')->assertForbidden();
    }

    public function test_antrean_kaprodi_hanya_berisi_pengajuan_yang_sudah_diverifikasi_tu(): void
    {
        $internship = $this->submit($this->newStudent(), $this->magang(['company_name' => 'PT Antrean Uji']));
        $kaprodi = $this->user('kaprodi@kp.test');

        $this->actingAs($kaprodi)->get('/kaprodi/persetujuan')->assertOk()->assertDontSee('PT Antrean Uji');
        $this->actingAs($this->user('tu@kp.test'))->patch("/tu/verifikasi/{$internship->id}", ['decision' => 'approve']);
        $this->actingAs($kaprodi)->get('/kaprodi/persetujuan')->assertOk()->assertSee('PT Antrean Uji');
    }

    // ---------- TU Fakultas: aktivasi Company Supervisor ----------

    public function test_hanya_tu_yang_membuka_dan_memproses_aktivasi(): void
    {
        $tari = Internship::where('student_id', $this->user('tari@kp.test')->id)->firstOrFail();
        $payload = ['company_id' => $tari->company_id, 'email' => $tari->supervisor_email];

        foreach (['kaprodi@kp.test', 'admin@kp.test', 'supervisor@kp.test', 'mahasiswa@kp.test'] as $email) {
            $this->actingAs($this->user($email))->get('/tu/aktivasi')->assertForbidden();
            $this->actingAs($this->user($email))->post('/tu/aktivasi/undang', $payload)->assertForbidden();
        }

        $this->actingAs($this->user('tu@kp.test'))->get('/tu/aktivasi')->assertOk()->assertSee('Hendra Gunawan')->assertSee('Tari Anjani');
        $this->actingAs($this->user('tu@kp.test'))->get('/dashboard')->assertOk()->assertSee('Menunggu aktivasi');
    }

    public function test_satu_undangan_untuk_beberapa_mahasiswa_dengan_email_supervisor_sama(): void
    {
        $a = $this->approve($this->submit($this->newStudent('2310511111'), $this->magang()));
        $b = $this->approve($this->submit($this->newStudent('2310512222'), $this->magang(['supervisor_email' => 'bima@contoh.test'])));
        $this->assertSame($a->company_id, $b->company_id, 'Nama perusahaan sama dipakai ulang');

        $this->actingAs($this->user('tu@kp.test'))
            ->post('/tu/aktivasi/undang', ['company_id' => $a->company_id, 'email' => 'bima@contoh.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('invitation_link');

        $invitation = Invitation::where('email', 'bima@contoh.test')->firstOrFail();
        $this->assertSame('supervisor', $invitation->role);

        $this->get("/undangan/{$invitation->token}")->assertOk()->assertSee('Aktivasi akun')
            ->assertSee('Mahasiswa Baru 2310511111')->assertSee('Mahasiswa Baru 2310512222');

        $this->post("/undangan/{$invitation->token}", [
            'name' => 'Bima Saputra',
            'password' => 'sandi-rahasia-1',
            'password_confirmation' => 'sandi-rahasia-1',
        ])->assertRedirect('/login');

        $bima = $this->user('bima@contoh.test');
        $this->assertSame('supervisor', $bima->role);
        $this->assertTrue($a->fresh()->isSupervisedBy($bima));
        $this->assertTrue($b->fresh()->isSupervisedBy($bima));
        $this->assertDatabaseHas('company_staff_profiles', ['user_id' => $bima->id, 'company_id' => $a->company_id, 'job_title' => 'Engineering Manager']);
        $this->assertFalse(Internship::awaitingSupervisor()->whereKey([$a->id, $b->id])->exists());

        $this->post('/login', ['email' => 'bima@contoh.test', 'password' => 'sandi-rahasia-1'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Company Supervisor');
    }

    public function test_tu_menautkan_akun_supervisor_yang_sudah_ada_tanpa_akun_kedua(): void
    {
        $internship = $this->approve($this->submit($this->newStudent(), $this->magang([
            'company_name' => 'PT Nusantara Digital', 'supervisor_email' => 'supervisor@kp.test',
        ])));
        $anita = $this->user('supervisor@kp.test');

        $this->actingAs($this->user('tu@kp.test'))
            ->post('/tu/aktivasi/undang', ['company_id' => $internship->company_id, 'email' => 'supervisor@kp.test'])
            ->assertSessionHasErrors('aturan');

        $this->actingAs($this->user('tu@kp.test'))
            ->post('/tu/aktivasi/tautkan', ['company_id' => $internship->company_id, 'email' => 'supervisor@kp.test'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($internship->fresh()->isSupervisedBy($anita));
        $this->assertSame(1, User::where('email', 'supervisor@kp.test')->count());
    }

    public function test_tu_tidak_bisa_menautkan_email_milik_peran_lain(): void
    {
        $internship = $this->approve($this->submit($this->newStudent(), $this->magang(['supervisor_email' => 'kaprodi@kp.test'])));

        $this->actingAs($this->user('tu@kp.test'))
            ->post('/tu/aktivasi/tautkan', ['company_id' => $internship->company_id, 'email' => 'kaprodi@kp.test'])
            ->assertSessionHasErrors('aturan');

        $this->assertNull($internship->fresh()->supervisor);
    }

    public function test_tu_mengoreksi_email_supervisor_yang_salah_ketik(): void
    {
        $tari = Internship::where('student_id', $this->user('tari@kp.test')->id)->firstOrFail();

        $this->actingAs($this->user('tu@kp.test'))
            ->patch('/tu/aktivasi/email', ['company_id' => $tari->company_id, 'email' => $tari->supervisor_email, 'new_email' => 'Hendra@DataPrima.test'])
            ->assertSessionHasNoErrors();

        $this->assertSame('hendra@dataprima.test', $tari->fresh()->supervisor_email);
    }

    public function test_pengajuan_yang_belum_disetujui_tidak_bisa_diaktifkan(): void
    {
        $nadia = Internship::where('student_id', $this->user('nadia@kp.test')->id)->firstOrFail();

        $this->actingAs($this->user('tu@kp.test'))
            ->post('/tu/aktivasi/undang', ['company_id' => $nadia->company_id, 'email' => $nadia->supervisor_email])
            ->assertSessionHasErrors('aturan');

        $this->assertDatabaseMissing('invitations', ['email' => $nadia->supervisor_email]);
    }

    // ---------- Undangan ----------

    private function invitationForTari(): Invitation
    {
        $tari = Internship::where('student_id', $this->user('tari@kp.test')->id)->firstOrFail();
        $this->actingAs($this->user('tu@kp.test'))->post('/tu/aktivasi/undang', ['company_id' => $tari->company_id, 'email' => $tari->supervisor_email]);
        auth()->logout();

        return Invitation::where('email', $tari->supervisor_email)->firstOrFail();
    }

    public function test_undangan_tidak_bisa_dipakai_dua_kali(): void
    {
        $invitation = $this->invitationForTari();
        $data = ['name' => 'Hendra Gunawan', 'password' => 'sandi-rahasia-1', 'password_confirmation' => 'sandi-rahasia-1'];

        $this->post("/undangan/{$invitation->token}", $data)->assertRedirect('/login');
        $this->post("/undangan/{$invitation->token}", $data)->assertSessionHasErrors('aturan');

        $this->assertSame(1, User::where('email', $invitation->email)->count());
    }

    public function test_undangan_kedaluwarsa_tidak_bisa_dipakai(): void
    {
        $invitation = $this->invitationForTari();
        $invitation->update(['expires_at' => now()->subDay()]);

        $this->get("/undangan/{$invitation->token}")->assertOk()->assertSee('Undangan tidak berlaku');
        $this->post("/undangan/{$invitation->token}", ['name' => 'X', 'password' => 'sandi-rahasia-1', 'password_confirmation' => 'sandi-rahasia-1'])
            ->assertSessionHasErrors('aturan');

        $this->assertDatabaseMissing('users', ['email' => $invitation->email]);
    }

    public function test_undangan_ulang_membatalkan_undangan_lama(): void
    {
        $old = $this->invitationForTari();
        $tari = Internship::where('student_id', $this->user('tari@kp.test')->id)->firstOrFail();
        $this->actingAs($this->user('tu@kp.test'))->post('/tu/aktivasi/undang', ['company_id' => $tari->company_id, 'email' => $tari->supervisor_email]);

        $this->assertFalse($old->fresh()->isUsable());
        $this->assertSame(1, Invitation::where('email', $tari->supervisor_email)->where('expires_at', '>', now())->count());
    }

    public function test_token_undangan_yang_salah_menghasilkan_404(): void
    {
        $this->get('/undangan/token-tidak-ada')->assertNotFound();
    }

    public function test_kata_sandi_undangan_minimal_8_karakter_dan_harus_cocok(): void
    {
        $invitation = $this->invitationForTari();

        $this->post("/undangan/{$invitation->token}", ['name' => 'X', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->post("/undangan/{$invitation->token}", ['name' => 'X', 'password' => 'sandi-rahasia-1', 'password_confirmation' => 'beda'])
            ->assertSessionHasErrors('password');
    }
}
