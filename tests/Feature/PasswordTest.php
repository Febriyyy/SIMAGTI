<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Ganti kata sandi sendiri, atur ulang oleh admin, dan wajib ganti kata sandi sementara. */
class PasswordTest extends TestCase
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

    public function test_semua_peran_bisa_membuka_halaman_ganti_kata_sandi(): void
    {
        foreach (['admin@kp.test', 'kaprodi@kp.test', 'tu@kp.test', 'dospem@kp.test', 'supervisor@kp.test', 'mahasiswa@kp.test'] as $email) {
            $this->actingAs($this->user($email))->get('/akun/sandi')->assertOk()
                ->assertSee('Ganti kata sandi')->assertSee('Kata sandi saat ini');
        }

        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')->assertSee(route('akun.sandi'), false);
    }

    public function test_validasi_ganti_kata_sandi(): void
    {
        $user = $this->user('mahasiswa@kp.test');
        $this->actingAs($user);

        $this->put('/akun/sandi', ['current_password' => 'salah', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasErrors('current_password');
        $this->put('/akun/sandi', ['current_password' => 'password', 'password' => 'pendek1', 'password_confirmation' => 'pendek1'])
            ->assertSessionHasErrors('password');
        $this->put('/akun/sandi', ['current_password' => 'password', 'password' => 'hanyahuruf', 'password_confirmation' => 'hanyahuruf'])
            ->assertSessionHasErrors('password');
        $this->put('/akun/sandi', ['current_password' => 'password', 'password' => '1234567890', 'password_confirmation' => '1234567890'])
            ->assertSessionHasErrors('password');
        $this->put('/akun/sandi', ['current_password' => 'password', 'password' => 'baru12345', 'password_confirmation' => 'beda12345'])
            ->assertSessionHasErrors('password');

        $user->update(['password' => 'sama12345']);
        $this->put('/akun/sandi', ['current_password' => 'sama12345', 'password' => 'sama12345', 'password_confirmation' => 'sama12345'])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('sama12345', $user->fresh()->password));
    }

    public function test_ganti_kata_sandi_berhasil_dan_token_api_dihapus(): void
    {
        $user = $this->user('mahasiswa@kp.test');
        $user->createToken('ponsel');
        $oldRemember = $user->remember_token;

        $this->actingAs($user)
            ->put('/akun/sandi', ['current_password' => 'password', 'password' => 'Simagti2027', 'password_confirmation' => 'Simagti2027'])
            ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertTrue(Hash::check('Simagti2027', $user->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotSame($oldRemember, $user->remember_token);
        $this->assertTrue(ActivityLog::where('action', 'akun.sandi')->where('user_id', $user->id)->exists());

        // Sesi yang sedang dipakai tetap berjalan
        $this->get('/dashboard')->assertOk();
    }

    public function test_admin_mengatur_ulang_kata_sandi_dan_pemilik_wajib_menggantinya(): void
    {
        $student = $this->user('mahasiswa@kp.test');
        $student->createToken('ponsel');

        $response = $this->actingAs($this->user('admin@kp.test'))
            ->from('/admin/akun')
            ->post("/admin/akun/{$student->id}/sandi")
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/akun');

        $temporary = session('temporary_password')['password'];
        $this->assertSame(10, strlen($temporary));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $temporary);

        $student->refresh();
        $this->assertTrue($student->must_change_password);
        $this->assertTrue(Hash::check($temporary, $student->password));
        $this->assertSame(0, $student->tokens()->count());
        $this->assertTrue(ActivityLog::where('action', 'akun.sandi.reset')->where('subject_id', $student->id)->exists());

        // Kartu kata sandi sementara tampil sekali di halaman Akun
        $this->followRedirects($response)->assertSee($temporary)->assertSee('Hanya tampil sekali');
        $this->get('/admin/akun')->assertDontSee($temporary);

        // Pemilik akun masuk dengan kata sandi sementara lalu diarahkan ke halaman ganti kata sandi
        auth()->logout();
        $this->post('/login', ['email' => 'mahasiswa@kp.test', 'password' => $temporary])->assertRedirect(route('dashboard'));
        $this->get('/dashboard')->assertRedirect(route('akun.sandi'));
        $this->get('/kegiatan')->assertRedirect(route('akun.sandi'));
        $this->get('/akun/sandi')->assertOk()->assertSee('kata sandi sementara');

        $this->put('/akun/sandi', ['current_password' => $temporary, 'password' => 'Rahasia2026', 'password_confirmation' => 'Rahasia2026'])
            ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

        $this->assertFalse($student->fresh()->must_change_password);
        $this->get('/dashboard')->assertOk();
    }

    public function test_akun_wajib_ganti_tidak_bisa_memakai_api_dan_bisa_keluar(): void
    {
        $student = $this->user('mahasiswa@kp.test');
        $student->update(['must_change_password' => true]);

        $this->actingAs($student)->post('/logout')->assertRedirect(route('login'));

        $this->withToken($student->createToken('x')->plainTextToken)->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_bukan_admin_ditolak_dan_admin_tidak_bisa_mengatur_ulang_diri_sendiri(): void
    {
        $student = $this->user('mahasiswa@kp.test');

        foreach (['kaprodi@kp.test', 'tu@kp.test', 'dospem@kp.test', 'supervisor@kp.test', 'mahasiswa@kp.test'] as $email) {
            $this->actingAs($this->user($email))->post("/admin/akun/{$student->id}/sandi")->assertForbidden();
        }
        $this->assertFalse($student->fresh()->must_change_password);

        $admin = $this->user('admin@kp.test');
        $this->actingAs($admin)->post("/admin/akun/{$admin->id}/sandi")->assertSessionHasErrors('password');
        $this->assertFalse($admin->fresh()->must_change_password);
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));

        $this->get('/admin/akun')->assertSee('Atur ulang sandi');
    }

    public function test_bantuan_lupa_kata_sandi_mengarahkan_ke_admin(): void
    {
        $this->get('/login')->assertOk()->assertSee('Lupa kata sandi?')->assertSee('Hubungi admin');
    }
}
