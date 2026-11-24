<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Lupa kata sandi: tautan lewat email, sekali pakai, tidak membocorkan email terdaftar. */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    public function test_tautan_dikirim_dan_kata_sandi_bisa_diatur_ulang(): void
    {
        Notification::fake();
        $rina = User::where('email', 'mahasiswa@kp.test')->firstOrFail();

        $this->get('/login')->assertOk()->assertSee(route('password.request'), false);
        $this->get('/lupa-sandi')->assertOk()->assertSee('Lupa kata sandi');
        $this->post('/lupa-sandi', ['email' => $rina->email])->assertSessionHas('status', PasswordResetController::SENT);

        $token = null;
        Notification::assertSentTo($rina, ResetPassword::class, function (ResetPassword $n) use (&$token, $rina) {
            $token = $n->token;
            $mail = $n->toMail($rina);

            return str_contains($mail->subject, 'Atur ulang kata sandi') && str_contains($mail->actionUrl, '/atur-sandi/');
        });

        $this->get('/atur-sandi/'.$token.'?email='.urlencode($rina->email))->assertOk()->assertSee('Atur ulang kata sandi');

        // Kata sandi tidak sama: ditolak
        $this->post('/atur-sandi', ['token' => $token, 'email' => $rina->email, 'password' => 'sandibaru123', 'password_confirmation' => 'beda12345'])
            ->assertSessionHasErrors('password');

        $this->post('/atur-sandi', ['token' => $token, 'email' => $rina->email, 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123'])
            ->assertRedirect('/login');
        $this->assertTrue(Hash::check('sandibaru123', $rina->fresh()->password));

        // Tautan sekali pakai
        $this->post('/atur-sandi', ['token' => $token, 'email' => $rina->email, 'password' => 'lainlagi123', 'password_confirmation' => 'lainlagi123'])
            ->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $rina->email, 'password' => 'sandibaru123'])->assertRedirect('/dashboard');
    }

    public function test_email_tidak_dikenal_atau_akun_nonaktif_tidak_dibedakan(): void
    {
        Notification::fake();
        $dimas = User::where('email', 'dimas@kp.test')->firstOrFail();
        $dimas->update(['is_active' => false]);

        $this->post('/lupa-sandi', ['email' => 'tidak.ada@contoh.test'])->assertSessionHas('status', PasswordResetController::SENT);
        $this->post('/lupa-sandi', ['email' => $dimas->email])->assertSessionHas('status', PasswordResetController::SENT);
        Notification::assertNothingSent();

        $this->post('/atur-sandi', ['token' => 'token-palsu', 'email' => $dimas->email, 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123'])
            ->assertSessionHasErrors('email');
    }

    public function test_kata_sandi_sementara_ikut_terlepas_setelah_atur_ulang(): void
    {
        Notification::fake();
        $rina = User::where('email', 'mahasiswa@kp.test')->firstOrFail();
        $rina->update(['must_change_password' => true]);

        $this->post('/lupa-sandi', ['email' => $rina->email]);
        $token = null;
        Notification::assertSentTo($rina, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->post('/atur-sandi', ['token' => $token, 'email' => $rina->email, 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123']);

        $this->assertFalse($rina->fresh()->must_change_password);
    }
}
