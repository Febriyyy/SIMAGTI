<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Silabus mahasiswa: tujuan hard skill, soft skill, attitude; disetujui Company Supervisor (seminar: dospem). */
class SyllabusTest extends TestCase
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

    public function test_mahasiswa_menyusun_dan_mengirim_silabus(): void
    {
        $tari = $this->internshipOf('tari@kp.test');
        $student = $this->user('tari@kp.test');

        $this->actingAs($student)->get('/silabus')->assertOk()->assertSee('Hard skill')->assertSee('Soft skill')->assertSee('Attitude');

        $this->post('/silabus', ['category' => 'hard', 'title' => 'Membuat dashboard Power BI'])->assertRedirect();
        $this->post('/silabus/kirim')->assertSessionHasErrors('silabus'); // soft skill dan attitude masih kosong

        $this->post('/silabus', ['category' => 'soft', 'title' => 'Presentasi hasil ke tim']);
        $this->post('/silabus', ['category' => 'attitude', 'title' => 'Disiplin waktu']);
        $this->post('/silabus', ['category' => 'lainnya', 'title' => 'x'])->assertSessionHasErrors('category');

        $this->post('/silabus/kirim')->assertSessionHasNoErrors();
        $this->assertSame('submitted', $tari->fresh()->syllabus_status);

        // Setelah dikirim, silabus terkunci
        $item = $tari->syllabusItems()->first();
        $this->patch("/silabus/{$item->id}", ['category' => 'hard', 'title' => 'Diubah'])->assertForbidden();
        $this->post('/silabus', ['category' => 'hard', 'title' => 'Tambah'])->assertForbidden();
    }

    public function test_supervisor_menyetujui_atau_meminta_revisi(): void
    {
        $dimas = $this->internshipOf('dimas@kp.test'); // silabus menunggu
        $supervisor = $this->user('supervisor2@kp.test');

        $this->actingAs($supervisor)->get("/pantau/{$dimas->id}?tab=silabus")->assertOk()->assertSee('Silabus menunggu persetujuan Anda');

        $this->patch("/pantau/{$dimas->id}/silabus", ['keputusan' => 'revisi'])->assertSessionHasErrors('catatan');
        $this->patch("/pantau/{$dimas->id}/silabus", ['keputusan' => 'revisi', 'catatan' => 'Tambahkan target SQL lanjutan.'])->assertRedirect();

        $fresh = $dimas->fresh();
        $this->assertSame('revision', $fresh->syllabus_status);
        $this->assertSame($supervisor->id, $fresh->syllabus_reviewed_by);

        $this->actingAs($this->user('dimas@kp.test'))->get('/silabus')->assertOk()->assertSee('Tambahkan target SQL lanjutan.');
        $this->get('/status-kp')->assertOk()->assertSee('Perlu revisi');

        $this->post('/silabus', ['category' => 'hard', 'title' => 'Window function SQL'])->assertRedirect();
        $this->post('/silabus/kirim')->assertSessionHasNoErrors();

        $this->actingAs($supervisor)->patch("/pantau/{$dimas->id}/silabus", ['keputusan' => 'setujui'])->assertRedirect();
        $this->assertSame('approved', $dimas->fresh()->syllabus_status);

        // Tidak ada lagi yang menunggu
        $this->patch("/pantau/{$dimas->id}/silabus", ['keputusan' => 'setujui'])->assertForbidden();
    }

    public function test_hanya_peninjau_yang_berhak_menyetujui(): void
    {
        $dimas = $this->internshipOf('dimas@kp.test');

        foreach (['supervisor@kp.test', 'dospem@kp.test', 'kaprodi@kp.test', 'dimas@kp.test'] as $email) {
            $this->actingAs($this->user($email))->patch("/pantau/{$dimas->id}/silabus", ['keputusan' => 'setujui'])->assertForbidden();
        }

        $this->assertSame('submitted', $dimas->fresh()->syllabus_status);
    }

    public function test_silabus_seminar_disetujui_dosen_pembimbing(): void
    {
        $gilang = $this->internshipOf('gilang@kp.test');

        $this->actingAs($this->user('gilang@kp.test'))->post('/silabus/kirim')->assertSessionHasNoErrors();
        $this->actingAs($this->user('supervisor@kp.test'))->patch("/pantau/{$gilang->id}/silabus", ['keputusan' => 'setujui'])->assertForbidden();
        $this->actingAs($this->user('dospem@kp.test'))->patch("/pantau/{$gilang->id}/silabus", ['keputusan' => 'setujui'])->assertRedirect();

        $this->assertSame('approved', $gilang->fresh()->syllabus_status);
    }

    public function test_silabus_yang_disetujui_bisa_diajukan_ulang(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/silabus')->assertOk()->assertSee('Ajukan perubahan');
        $this->post('/silabus/ubah')->assertRedirect();

        $this->assertSame('draft', $rina->fresh()->syllabus_status);
        $this->post('/silabus', ['category' => 'soft', 'title' => 'Mentoring sesama intern'])->assertRedirect();
    }

    public function test_item_silabus_mahasiswa_lain_tidak_bisa_diubah(): void
    {
        $item = $this->internshipOf('gilang@kp.test')->syllabusItems()->firstOrFail();

        $this->actingAs($this->user('tari@kp.test'))->patch("/silabus/{$item->id}", ['category' => 'hard', 'title' => 'Bajak'])->assertNotFound();
        $this->actingAs($this->user('tari@kp.test'))->delete("/silabus/{$item->id}")->assertNotFound();
        $this->assertDatabaseHas('syllabus_items', ['id' => $item->id]);
    }
}
