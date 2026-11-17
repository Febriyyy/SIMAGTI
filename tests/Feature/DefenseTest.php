<?php

namespace Tests\Feature;

use App\Models\DefenseSchedule;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Nilai sidang KP oleh kaprodi: dicatat terpisah dari nilai akhir. */
class DefenseTest extends TestCase
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

    private function internshipOf(string $email): Internship
    {
        return Internship::where('student_id', $this->user($email)->id)->latest('id')->firstOrFail();
    }

    public function test_kaprodi_mengisi_nilai_sidang_tanpa_mengubah_nilai_akhir(): void
    {
        $kaprodi = $this->user('kaprodi@kp.test');
        $salsa = $this->internshipOf('salsa@kp.test');
        $salsa->defenseScore()->delete(); // data demo sudah punya nilai sidang; mulai dari kosong
        $finalBefore = $salsa->final_score;

        $this->actingAs($kaprodi)->get('/kaprodi/sidang')->assertOk()->assertSee('Perlu dijadwalkan')->assertSee('Salsa');

        $this->actingAs($kaprodi)->put("/kaprodi/sidang/{$salsa->id}", ['score' => 88.5, 'defended_at' => today()->toDateString()])
            ->assertRedirect('/kaprodi/sidang');

        $defense = $salsa->fresh()->defenseScore;
        $this->assertSame('88.50', (string) $defense->score);
        $this->assertNotNull($defense->letter_grade);
        $this->assertEquals($finalBefore, $salsa->fresh()->final_score, 'Nilai akhir tidak berubah');
        $this->assertSame(1, $this->user('salsa@kp.test')->notifications()->where('data', 'like', '%Nilai sidang KP sudah keluar%')->count());

        // Ubah nilai: huruf mutu dihitung ulang
        $this->actingAs($kaprodi)->put("/kaprodi/sidang/{$salsa->id}", ['score' => 40, 'defended_at' => today()->toDateString(), 'note' => 'Revisi'])->assertRedirect();
        $this->assertNotSame($defense->letter_grade, $salsa->fresh()->defenseScore->letter_grade);

        // Tampil di Status KP mahasiswa
        $this->actingAs($this->user('salsa@kp.test'))->get('/status-kp')->assertSee('Nilai sidang');
    }

    public function test_nilai_sidang_hanya_untuk_kaprodi_dan_magang_yang_sudah_selesai(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('dospem@kp.test'))->get('/kaprodi/sidang')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))
            ->put("/kaprodi/sidang/{$rina->id}", ['score' => 80, 'defended_at' => today()->toDateString()])
            ->assertSessionHasErrors('score');
        $this->assertNull($rina->fresh()->defenseScore);

        $salsa = $this->internshipOf('salsa@kp.test');
        $this->actingAs($this->user('kaprodi@kp.test'))
            ->put("/kaprodi/sidang/{$salsa->id}", ['score' => 120, 'defended_at' => today()->addDay()->toDateString()])
            ->assertSessionHasErrors(['score', 'defended_at']);
    }

    public function test_kaprodi_menjadwalkan_sidang_dan_pihak_terkait_diberi_tahu(): void
    {
        $kaprodi = $this->user('kaprodi@kp.test');
        $salsa = $this->internshipOf('salsa@kp.test');
        $salsa->defenseScore()->delete();
        $examiner = User::where('role', 'dospem')->where('id', '!=', $salsa->lecturer_id)->first() ?? $salsa->lecturer;
        $date = today()->addDays(3)->toDateString();

        $this->actingAs($kaprodi)->put("/kaprodi/sidang/{$salsa->id}/jadwal", ['tanggal' => $date, 'jam' => '09:30', 'cara' => 'luring', 'tempat' => 'Ruang B2.04', 'penguji' => $examiner->id])
            ->assertRedirect('/kaprodi/sidang');

        $schedule = $salsa->fresh()->defenseSchedule;
        $this->assertSame($date.' 09:30', $schedule->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame(1, $this->user('salsa@kp.test')->notifications()->where('data', 'like', '%Sidang KP dijadwalkan%')->count());
        $this->assertSame(1, $examiner->notifications()->where('data', 'like', '%Sidang KP%')->count());

        $this->actingAs($kaprodi)->get('/kaprodi/sidang')->assertSee('Jadwal sidang')->assertSee('Ruang B2.04');
        $this->actingAs($this->user('salsa@kp.test'))->get('/status-kp')->assertSee('Ruang B2.04');

        // Ubah jadwal: tetap satu jadwal, tanggal lampau ditolak
        $this->actingAs($kaprodi)->put("/kaprodi/sidang/{$salsa->id}/jadwal", ['tanggal' => today()->subDay()->toDateString(), 'jam' => '09:30', 'cara' => 'daring', 'tempat' => 'https://meet.example/x'])
            ->assertSessionHasErrorsIn('jadwal'.$salsa->id, 'tanggal');
        $this->actingAs($kaprodi)->put("/kaprodi/sidang/{$salsa->id}/jadwal", ['tanggal' => $date, 'jam' => '13:00', 'cara' => 'daring', 'tempat' => 'https://meet.example/x'])->assertRedirect();
        $this->assertSame('daring', $salsa->fresh()->defenseSchedule->mode);
        $this->assertSame(1, DefenseSchedule::where('internship_id', $salsa->id)->count());

        $this->actingAs($this->user('dospem@kp.test'))->put("/kaprodi/sidang/{$salsa->id}/jadwal", ['tanggal' => $date, 'jam' => '09:00', 'cara' => 'luring', 'tempat' => 'R1'])->assertForbidden();
    }
}
