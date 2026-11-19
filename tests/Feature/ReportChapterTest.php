<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\ReportChapter;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Laporan KP per bab: bab berisi informasi perusahaan disetujui Company Supervisor dulu, lalu dosen pembimbing. */
class ReportChapterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
        Storage::fake('local');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function internshipOf(string $email): Internship
    {
        return Internship::where('student_id', $this->user($email)->id)->latest('id')->firstOrFail();
    }

    private function chapter(Internship $internship, int $position): ReportChapter
    {
        return $internship->reportChapters()->where('position', $position)->firstOrFail();
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('bab.pdf', 120, 'application/pdf');
    }

    public function test_bab_perusahaan_melewati_supervisor_dulu_lalu_dosen(): void
    {
        $student = $this->user('fajar@kp.test');
        $kp = $this->internshipOf('fajar@kp.test');
        $supervisor = $kp->supervisor;
        $lecturer = $kp->lecturer;

        // Bab bawaan dibuat saat halaman Laporan KP dibuka
        $this->actingAs($student)->get("/laporan-kp/{$kp->id}")->assertOk()->assertSee('Laporan KP')->assertSee('dari 4 bab disetujui')->assertSee('Gambaran Umum Perusahaan');
        $this->assertSame(4, $kp->reportChapters()->count());

        $bab2 = $this->chapter($kp, 2);
        $this->actingAs($student)->post("/dokumen/laporan/{$bab2->id}", ['berkas' => $this->pdf()])->assertSessionHasNoErrors();
        $bab2->refresh();
        $this->assertSame('menunggu_supervisor', $bab2->status);
        $this->assertSame(1, $bab2->version);

        // Dosen belum boleh membaca sebelum perusahaan menyetujui
        $this->actingAs($lecturer)->get("/laporan-bab/{$bab2->id}/berkas")->assertForbidden();
        $this->actingAs($lecturer)->patch("/laporan-bab/{$bab2->id}", ['keputusan' => 'setujui'])->assertSessionHasErrors('laporan');

        // Supervisor meminta revisi (wajib catatan), mahasiswa mengunggah ulang, supervisor menyetujui
        $this->actingAs($supervisor)->get('/laporan-bab')->assertOk()->assertSee('Gambaran Umum Perusahaan');
        $this->actingAs($supervisor)->get("/laporan-bab/{$bab2->id}/berkas")->assertOk();
        $this->actingAs($supervisor)->patch("/laporan-bab/{$bab2->id}", ['keputusan' => 'revisi'])->assertSessionHasErrors('catatan');
        $this->actingAs($supervisor)->patch("/laporan-bab/{$bab2->id}", ['keputusan' => 'revisi', 'catatan' => 'Hapus angka omzet.'])->assertSessionHasNoErrors();
        $this->assertSame('revisi', $bab2->fresh()->status);
        $this->actingAs($student)->get("/laporan-kp/{$kp->id}")->assertSee('Hapus angka omzet.');

        $this->actingAs($student)->post("/dokumen/laporan/{$bab2->id}", ['berkas' => $this->pdf()]);
        $this->actingAs($supervisor)->patch("/laporan-bab/{$bab2->id}", ['keputusan' => 'setujui'])->assertSessionHasNoErrors();
        $bab2->refresh();
        $this->assertSame('menunggu_dosen', $bab2->status);
        $this->assertSame(2, $bab2->version);

        // Kini dosen bisa membaca dan menyetujui
        $this->actingAs($lecturer)->get('/laporan-bab')->assertSee('Gambaran Umum Perusahaan');
        $this->actingAs($lecturer)->get("/laporan-bab/{$bab2->id}/berkas")->assertOk();
        $this->actingAs($lecturer)->patch("/laporan-bab/{$bab2->id}", ['keputusan' => 'setujui'])->assertSessionHasNoErrors();
        $this->assertSame('disetujui', $bab2->fresh()->status);

        // Bab yang sudah disetujui tidak bisa diganti
        $this->actingAs($student)->post("/dokumen/laporan/{$bab2->id}", ['berkas' => $this->pdf()])->assertSessionHasErrors('berkas');
    }

    public function test_bab_tanpa_data_perusahaan_langsung_ke_dosen_dan_tidak_terlihat_supervisor(): void
    {
        $student = $this->user('fajar@kp.test');
        $kp = $this->internshipOf('fajar@kp.test');
        $this->actingAs($student)->get("/laporan-kp/{$kp->id}");

        $bab1 = $this->chapter($kp, 1);
        $this->actingAs($student)->post("/dokumen/laporan/{$bab1->id}", ['berkas' => $this->pdf()]);
        $this->assertSame('menunggu_dosen', $bab1->fresh()->status);

        $this->actingAs($kp->supervisor)->get("/laporan-bab/{$bab1->id}/berkas")->assertForbidden();
        $this->actingAs($kp->supervisor)->patch("/laporan-bab/{$bab1->id}", ['keputusan' => 'setujui'])->assertSessionHasErrors('laporan');
        $this->actingAs($kp->lecturer)->get("/laporan-bab/{$bab1->id}/berkas")->assertOk();
    }

    public function test_orang_lain_tidak_bisa_mengunggah_atau_membuka(): void
    {
        $kp = $this->internshipOf('fajar@kp.test');
        $this->actingAs($this->user('fajar@kp.test'))->get("/laporan-kp/{$kp->id}");
        $bab1 = $this->chapter($kp, 1);

        $this->actingAs($this->user('mahasiswa@kp.test'))->post("/dokumen/laporan/{$bab1->id}", ['berkas' => $this->pdf()])->assertForbidden();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/laporan-bab/{$bab1->id}/berkas")->assertForbidden();
        $this->actingAs($this->user('fajar@kp.test'))->post("/dokumen/laporan/{$bab1->id}", ['berkas' => UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('berkas');
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/laporan-bab')->assertForbidden();
    }
}
