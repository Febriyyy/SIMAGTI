<?php

namespace Tests\Feature;

use App\Actions\Applications\ReviewKaprodiApproval;
use App\Actions\Results\FinalizeResult;
use App\Models\ChangeRequest;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\InternshipDocument;
use App\Models\User;
use App\Notifications\KpNotice;
use App\Support\ActionItems;
use App\Support\KpProgress;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Alur KP: konfirmasi supervisor, bimbingan, dokumen, hasil, perubahan, notifikasi. */
class StageTwoFlowTest extends TestCase
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

    // ---------- Konfirmasi Company Supervisor ----------

    public function test_supervisor_mengonfirmasi_penempatan_dengan_satu_klik(): void
    {
        $dimas = $this->internshipOf('dimas@kp.test');
        $ratna = $this->user('supervisor2@kp.test');

        $this->assertContains('waiting', array_column(array_filter(KpProgress::steps($dimas), fn ($s) => $s['key'] === 'supervisor'), 'state'));
        $this->assertNotEmpty(array_filter(ActionItems::for($ratna), fn ($i) => str_contains($i['title'], 'Konfirmasi')));

        $this->actingAs($ratna)->patch("/pantau/{$dimas->id}/konfirmasi", ['keputusan' => 'benar'])->assertSessionHasNoErrors();

        $this->assertSame('confirmed', $dimas->fresh()->supervisor_confirmation);
        $step = collect(KpProgress::steps($dimas->fresh()))->firstWhere('key', 'supervisor');
        $this->assertSame('done', $step['state']);
        $this->assertSame(1, $this->user('dimas@kp.test')->unreadNotifications()->count());
    }

    public function test_sanggahan_wajib_catatan_lalu_tu_bisa_melepas_supervisor(): void
    {
        $dimas = $this->internshipOf('dimas@kp.test');
        $ratna = $this->user('supervisor2@kp.test');

        $this->actingAs($ratna)->patch("/pantau/{$dimas->id}/konfirmasi", ['keputusan' => 'salah'])->assertSessionHasErrors('catatan');
        $this->actingAs($ratna)->patch("/pantau/{$dimas->id}/konfirmasi", ['keputusan' => 'salah', 'catatan' => 'Posisinya Data Engineer.'])->assertSessionHasNoErrors();
        $this->assertSame('disputed', $dimas->fresh()->supervisor_confirmation);

        $tu = $this->user('tu@kp.test');
        $this->actingAs($tu)->get('/tu/aktivasi')->assertOk()->assertSee('Data penempatan dipertanyakan')->assertSee('Posisinya Data Engineer.');

        $this->actingAs($tu)->patch("/tu/aktivasi/{$dimas->id}/lepas")->assertSessionHasNoErrors();
        $dimas->refresh();
        $this->assertNull($dimas->supervisor);
        $this->assertTrue(Internship::awaitingSupervisor()->whereKey($dimas->id)->exists());
        $this->assertFalse($dimas->isSupervisedBy($ratna));
    }

    // ---------- Bimbingan dan hasil KP ----------

    public function test_bimbingan_dicatat_mahasiswa_dan_dikonfirmasi_dosen(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('mahasiswa@kp.test'))->post('/bimbingan', [
            'held_on' => now()->addDay()->toDateString(), 'mode' => 'daring', 'topic' => 'Masa depan',
        ])->assertSessionHasErrors('held_on');

        $this->actingAs($this->user('mahasiswa@kp.test'))->post('/bimbingan', [
            'held_on' => now()->toDateString(), 'mode' => 'luring', 'topic' => 'Bab 3 laporan', 'notes' => 'Diskusi metodologi',
        ])->assertSessionHasNoErrors();
        $session = GuidanceSession::where('topic', 'Bab 3 laporan')->firstOrFail();
        $this->assertSame('pending', $session->status);

        $this->actingAs($this->user('dospem@kp.test'))->patch("/antrean-bimbingan/{$session->id}", ['keputusan' => 'konfirmasi'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $session->fresh()->status);
        $this->assertSame(4, $rina->fresh()->confirmedGuidanceCount());

        // Sesi terkonfirmasi tidak bisa dihapus mahasiswa
        $this->actingAs($this->user('mahasiswa@kp.test'))->delete("/bimbingan/{$session->id}")->assertForbidden();
    }

    public function test_hasil_kp_menunggu_syarat_lalu_menutup_kp(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test'); // 5 bimbingan terkonfirmasi, penilaian sudah dikirim (belum divalidasi)
        $dospem = $this->user('dospem@kp.test');

        $this->assertNotEmpty(FinalizeResult::blockers($fajar));
        $this->actingAs($dospem)->patch("/hasil/{$fajar->id}", ['result' => 'diterima'])->assertSessionHasErrors('result');

        $this->actingAs($dospem)->patch("/validasi/{$fajar->id}", ['keputusan' => 'validasi', 'catatan' => 'Sesuai.']);
        $this->assertSame([], FinalizeResult::blockers($fajar->fresh()));

        $this->actingAs($dospem)->patch("/hasil/{$fajar->id}", ['result' => 'tidak'])->assertSessionHasErrors('result_note');
        $this->actingAs($dospem)->patch("/hasil/{$fajar->id}", ['result' => 'diterima', 'result_note' => 'Baik.'])->assertRedirect('/hasil');

        $fajar->refresh();
        $this->assertSame('selesai', $fajar->status);
        $this->assertSame('Diterima', $fajar->resultLabel());
        $this->assertSame('A', $fajar->final_grade, 'Nilai diambil dari penilaian akhir yang divalidasi');
        $this->assertNotNull($fajar->locked_at);
        $this->assertSame('done', collect(KpProgress::steps($fajar))->firstWhere('key', 'result')['state']);
    }

    // ---------- Dokumen ----------

    public function test_tu_menerbitkan_surat_pengantar_bernomor_dan_mahasiswa_mencetak(): void
    {
        $nadia = $this->internshipOf('nadia@kp.test');
        $letter = $nadia->document(InternshipDocument::LETTER);
        $this->assertSame('diminta', $letter->status);

        $this->actingAs($this->user('tu@kp.test'))->patch("/tu/surat/{$letter->id}")->assertSessionHasNoErrors();
        $letter->refresh();
        $this->assertSame('terbit', $letter->status);
        $this->assertMatchesRegularExpression('#^\d{3}/'.preg_quote(config('simagti.campus.letter_code'), '#').'/[IVX]+/\d{4}$#', $letter->number);

        $this->actingAs($this->user('nadia@kp.test'))->get("/dokumen/{$letter->id}/cetak")->assertOk()
            ->assertSee($letter->number)->assertSee('Permohonan Kerja Praktik')->assertSee('CV Karya Mandiri');
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/dokumen/{$letter->id}/cetak")->assertForbidden();
    }

    public function test_tu_menolak_surat_balasan_dan_mahasiswa_mengunggah_ulang(): void
    {
        Storage::fake('local');
        $tari = $this->internshipOf('tari@kp.test');
        $reply = $tari->document(InternshipDocument::REPLY);
        $due = $reply->due_on->copy();
        $tu = $this->user('tu@kp.test');

        $this->actingAs($tu)->patch("/tu/dokumen/{$reply->id}", ['keputusan' => 'tolak'])->assertSessionHasErrors();
        $this->actingAs($tu)->patch("/tu/dokumen/{$reply->id}", ['keputusan' => 'tolak', 'catatan' => 'Tidak ada cap perusahaan.'])->assertSessionHasNoErrors();
        $reply->refresh();
        $this->assertSame('ditolak', $reply->status);
        $this->assertTrue($reply->due_on->gte($due));

        $this->actingAs($this->user('tari@kp.test'))->post('/dokumen/unggah/surat_balasan', [
            'berkas' => UploadedFile::fake()->create('balasan.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('berkas');
        $this->actingAs($this->user('tari@kp.test'))->post('/dokumen/unggah/surat_balasan', [
            'berkas' => UploadedFile::fake()->create('balasan.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('diunggah', $reply->fresh()->status);

        $this->actingAs($tu)->patch("/tu/dokumen/{$reply->id}", ['keputusan' => 'terima'])->assertSessionHasNoErrors();
        $this->assertTrue($reply->fresh()->isComplete());
    }

    public function test_dokumen_dibuat_saat_kaprodi_menyetujui_kp_mandiri(): void
    {
        $nadia = $this->internshipOf('nadia@kp.test');
        $nadia->approvals()->where('step', InternshipApproval::STEP_VERIFY)->update(['status' => 'approved', 'reviewed_at' => now()]);

        app(ReviewKaprodiApproval::class)
            ->handle($this->user('kaprodi@kp.test'), $nadia, true, $this->user('dospem@kp.test')->id);

        $reply = $nadia->fresh()->document(InternshipDocument::REPLY);
        $this->assertSame('menunggu', $reply->status);
        $this->assertTrue($reply->due_on->equalTo($nadia->start_date->copy()->addDays((int) config('simagti.rules.reply_letter_days'))));
        $this->assertSame(1, $nadia->fresh()->documents()->where('type', '!=', InternshipDocument::LETTER)->count(), 'Hanya surat balasan');
    }

    // ---------- Pengajuan perubahan ----------

    public function test_perpanjangan_disetujui_supervisor_lalu_kaprodi(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $change = ChangeRequest::where('internship_id', $fajar->id)->firstOrFail();
        $newEnd = $change->payload['new_end_date'];

        $this->actingAs($this->user('kaprodi@kp.test'))->patch("/antrean-perubahan/{$change->id}", ['keputusan' => 'setujui'])->assertForbidden();

        $this->actingAs($this->user('supervisor@kp.test'))->patch("/antrean-perubahan/{$change->id}", ['keputusan' => 'setujui', 'catatan' => 'Setuju.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('kaprodi', $change->fresh()->step);

        $this->actingAs($this->user('kaprodi@kp.test'))->patch("/antrean-perubahan/{$change->id}", ['keputusan' => 'setujui'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $change->fresh()->status);
        $this->assertSame($newEnd, $fajar->fresh()->end_date->toDateString());
    }

    public function test_pengunduran_diri_menutup_kp_dan_mahasiswa_bisa_mengajukan_lagi(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $student = $this->user('mahasiswa@kp.test');

        $this->actingAs($student)->post('/perubahan', ['type' => 'withdraw'])->assertSessionHasErrors('reason');
        $this->actingAs($student)->post('/perubahan', ['type' => 'withdraw', 'reason' => 'Alasan keluarga.'])->assertSessionHasNoErrors();
        $this->actingAs($student)->post('/perubahan', ['type' => 'withdraw', 'reason' => 'Lagi.'])->assertSessionHasErrors('type');

        $change = ChangeRequest::where('internship_id', $rina->id)->firstOrFail();
        $this->assertSame('kaprodi', $change->step);
        $this->actingAs($this->user('kaprodi@kp.test'))->patch("/antrean-perubahan/{$change->id}", ['keputusan' => 'tolak'])->assertSessionHasErrors('catatan');
        $this->actingAs($this->user('kaprodi@kp.test'))->patch("/antrean-perubahan/{$change->id}", ['keputusan' => 'setujui'])->assertSessionHasNoErrors();

        $rina->refresh();
        $this->assertSame('dibatalkan', $rina->status);
        $this->assertTrue($rina->isClosed());
        $this->actingAs($student)->get('/status-kp')->assertOk()->assertSee('Ajukan ulang');
    }

    public function test_supervisor_meminta_ganti_dan_tu_menyetujui(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $this->actingAs($this->user('supervisor@kp.test'))->post("/pantau/{$rina->id}/ganti-supervisor", [
            'supervisor_name' => 'Bima Saputra', 'supervisor_email' => 'Bima@NusantaraDigital.test', 'reason' => 'Saya pindah divisi.',
        ])->assertSessionHasNoErrors();

        $change = ChangeRequest::where('internship_id', $rina->id)->where('type', 'replace_supervisor')->firstOrFail();
        $this->assertSame('tu', $change->step);
        $this->actingAs($this->user('tu@kp.test'))->patch("/antrean-perubahan/{$change->id}", ['keputusan' => 'setujui'])->assertSessionHasNoErrors();

        $rina->refresh();
        $this->assertSame('bima@nusantaradigital.test', $rina->supervisor_email);
        $this->assertNull($rina->supervisor);
        $this->assertTrue(Internship::awaitingSupervisor()->whereKey($rina->id)->exists());
    }

    // ---------- Notifikasi ----------

    public function test_notifikasi_dibuka_ditandai_dibaca_dan_hanya_ke_alamat_internal(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $unread = $rina->unreadNotifications()->first();
        $this->actingAs($rina)->get("/notifikasi/{$unread->id}")->assertRedirect($unread->data['url']);
        $this->assertNotNull($unread->fresh()->read_at);

        $rina->notify(new KpNotice('Tautan luar', null, 'https://contoh-jahat.test/x'));
        $evil = $rina->notifications()->latest()->first();
        $this->actingAs($rina)->get("/notifikasi/{$evil->id}")->assertRedirect(route('notifikasi.index'));

        $other = $this->user('dimas@kp.test');
        $this->actingAs($other)->get("/notifikasi/{$evil->id}")->assertNotFound();
    }
}
