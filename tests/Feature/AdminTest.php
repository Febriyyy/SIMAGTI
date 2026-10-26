<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin: mengubah peran dan menonaktifkan akun. Peran Biro Kerjasama sudah tidak ada. */
class AdminTest extends TestCase
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

    private function fresh(string $role = 'mahasiswa'): User
    {
        return User::create(['name' => 'Akun Baru', 'email' => 'akun.baru@kp.test', 'password' => 'password', 'role' => $role, 'is_active' => true]);
    }

    public function test_peran_yang_tersedia(): void
    {
        $this->assertSame(['mahasiswa', 'supervisor', 'dospem', 'kaprodi', 'tu', 'admin'], array_keys(User::ROLE_LABELS));
        $this->assertSame(0, User::where('role', 'biro')->count());
    }

    public function test_admin_melihat_dan_menyaring_akun(): void
    {
        $this->actingAs($this->user('admin@kp.test'))->get('/admin/akun')->assertOk()
            ->assertSee('Rina Putri')->assertSee('Anita Wulandari')->assertSee('Nonaktifkan');

        $this->get('/admin/akun?peran=supervisor')->assertOk()->assertSee('Anita Wulandari')->assertDontSee('Rina Putri');
        $this->get('/admin/akun?cari=2310511033')->assertOk()->assertSee('Dimas Aditya')->assertDontSee('Rina Putri');

        foreach (['kaprodi@kp.test', 'tu@kp.test', 'mahasiswa@kp.test'] as $email) {
            $this->actingAs($this->user($email))->get('/admin/akun')->assertForbidden();
        }
    }

    public function test_admin_mengubah_peran_akun_yang_belum_terhubung_data_kp(): void
    {
        $account = $this->fresh();

        $this->actingAs($this->user('admin@kp.test'))->patch("/admin/akun/{$account->id}", ['role' => 'tu'])->assertSessionHasNoErrors();
        $this->assertSame('tu', $account->fresh()->role);
        $this->assertTrue(ActivityLog::where('action', 'akun.peran')->exists());

        $this->patch("/admin/akun/{$account->id}", ['role' => 'biro'])->assertSessionHasErrors('role');
    }

    public function test_peran_terkunci_bila_masih_terhubung_data_kp(): void
    {
        $admin = $this->user('admin@kp.test');

        $this->actingAs($admin)->patch('/admin/akun/'.$this->user('mahasiswa@kp.test')->id, ['role' => 'tu'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch('/admin/akun/'.$this->user('supervisor@kp.test')->id, ['role' => 'dospem'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch('/admin/akun/'.$this->user('dospem@kp.test')->id, ['role' => 'tu'])->assertSessionHasErrors('role');
        $this->assertSame('mahasiswa', $this->user('mahasiswa@kp.test')->role);
    }

    public function test_admin_menonaktifkan_akun_dan_sesi_akun_itu_berakhir(): void
    {
        $dimas = $this->user('dimas@kp.test');

        // Dimas sedang masuk
        $this->actingAs($dimas)->get('/dashboard')->assertOk();

        $this->actingAs($this->user('admin@kp.test'))->patch("/admin/akun/{$dimas->id}", ['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($dimas->fresh()->is_active);

        $this->actingAs($dimas->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'dimas@kp.test', 'password' => 'password'])->assertSessionHasErrors('email');

        $this->actingAs($this->user('admin@kp.test'))->patch("/admin/akun/{$dimas->id}", ['is_active' => 1])->assertSessionHasNoErrors();
        $this->post('/logout');
        $this->post('/login', ['email' => 'dimas@kp.test', 'password' => 'password'])->assertRedirect('/dashboard');
    }

    public function test_admin_tidak_bisa_mengubah_diri_sendiri_dan_minimal_satu_admin_aktif(): void
    {
        $admin = $this->user('admin@kp.test');

        $this->actingAs($admin)->patch("/admin/akun/{$admin->id}", ['is_active' => 0])->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch("/admin/akun/{$admin->id}", ['role' => 'tu'])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->is_active);

        // Admin kedua tidak bisa menonaktifkan admin terakhir yang aktif setelah dirinya dinonaktifkan
        $second = $this->fresh('admin');
        $this->actingAs($second)->patch("/admin/akun/{$admin->id}", ['is_active' => 0])->assertSessionHasNoErrors();
        $this->actingAs($admin->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertSame(1, User::where('role', 'admin')->where('is_active', true)->count());
    }
}
