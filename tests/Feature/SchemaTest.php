<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\ConversionCandidate;
use App\Models\Internship;
use App\Models\InternshipStaff;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\WeeklyMeeting;
use App\Models\WeeklyTask;
use App\Support\Grading;
use Database\Seeders\TestingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Menguji aturan yang dijaga langsung oleh struktur database dan model.
 */
class SchemaTest extends TestCase
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

    private function activeInternship(string $email): Internship
    {
        return Internship::where('student_id', $this->user($email)->id)->where('status', 'berjalan')->firstOrFail();
    }

    // ---------- Penempatan ----------

    public function test_satu_mahasiswa_hanya_boleh_punya_satu_penempatan_aktif(): void
    {
        $rina = $this->activeInternship('mahasiswa@kp.test');

        $this->expectException(QueryException::class);

        Internship::create([
            'student_id' => $rina->student_id,
            'company_id' => $rina->company_id,
            'status' => 'diajukan',
            'start_date' => now(),
            'end_date' => now()->addMonths(4),
        ]);
    }

    public function test_mahasiswa_boleh_punya_penempatan_baru_setelah_yang_lama_selesai(): void
    {
        $rina = $this->activeInternship('mahasiswa@kp.test');
        $rina->update(['status' => 'selesai']);

        $baru = Internship::create([
            'student_id' => $rina->student_id,
            'company_id' => $rina->company_id,
            'status' => 'diajukan',
            'start_date' => now(),
            'end_date' => now()->addMonths(4),
        ]);

        $this->assertSame($rina->student_id, $baru->active_student_id);
    }

    public function test_penempatan_selesai_otomatis_terkunci_dan_riwayat_status_tercatat(): void
    {
        $selesai = Internship::where('status', 'selesai')->firstOrFail();

        $this->assertTrue($selesai->isLocked());
        $this->assertNull($selesai->active_student_id);
        $this->assertDatabaseHas('internship_status_histories', [
            'internship_id' => $selesai->id,
            'from_status' => 'berjalan',
            'to_status' => 'selesai',
        ]);
    }

    public function test_task_pada_penempatan_terkunci_tidak_bisa_diubah(): void
    {
        $task = WeeklyTask::whereHas('internship', fn ($q) => $q->where('status', 'selesai'))->firstOrFail();

        $this->expectException(LogicException::class);

        $task->update(['title' => 'Diubah diam-diam']);
    }

    public function test_company_supervisor_diatur_per_penempatan(): void
    {
        $rina = $this->activeInternship('mahasiswa@kp.test');

        $this->assertTrue($rina->isSupervisedBy($this->user('supervisor@kp.test')));
        $this->assertFalse($rina->isSupervisedBy($this->user('supervisor2@kp.test')));
        $this->assertSame('Anita Wulandari', $rina->supervisor->name);
        $this->assertTrue($rina->isReviewedBy($this->user('supervisor@kp.test')));
        $this->assertFalse($rina->isReviewedBy($this->user('dospem@kp.test')), 'Magang ditinjau Company Supervisor');

        $gilang = $this->activeInternship('gilang@kp.test');
        $this->assertNull($gilang->company_id);
        $this->assertTrue($gilang->isReviewedBy($this->user('dospem@kp.test')), 'Seminar ditinjau dosen pembimbing');
    }

    public function test_supervisor_yang_sama_tidak_bisa_dipasang_dua_kali(): void
    {
        $rina = $this->activeInternship('mahasiswa@kp.test');
        $supervisor = $this->user('supervisor@kp.test');

        $rina->assignSupervisor($supervisor);
        $this->assertSame(1, $rina->staff()->where('user_id', $supervisor->id)->count());

        $this->expectException(QueryException::class);
        InternshipStaff::create(['internship_id' => $rina->id, 'user_id' => $supervisor->id, 'role' => InternshipStaff::ROLE_SUPERVISOR]);
    }

    public function test_kode_task_dan_status_mengikuti_kolom(): void
    {
        $rina = $this->activeInternship('mahasiswa@kp.test');
        $done = $rina->boardColumns()->where('category', 'done')->firstOrFail();
        $task = $rina->tasks()->where('status', 'todo')->firstOrFail();

        $this->assertMatchesRegularExpression('/^(PMS|DOC)-\d+$/', $task->key());
        $task->update(['board_column_id' => $done->id]);
        $this->assertSame('completed', $task->fresh()->status);

        // Nomor task unik per penempatan
        $this->expectException(QueryException::class);
        WeeklyTask::create(['internship_id' => $rina->id, 'number' => $task->number, 'title' => 'Kembar', 'created_by' => $rina->student_id]);
    }

    // ---------- Task dan meeting ----------

    public function test_satu_meeting_per_penempatan_per_minggu(): void
    {
        $rina = $this->activeInternship('mahasiswa@kp.test');

        $this->expectException(QueryException::class);

        WeeklyMeeting::create([
            'internship_id' => $rina->id,
            'week_number' => $rina->currentWeek(),
            'day' => 'senin',
            'time' => '09:00',
            'url' => 'https://zoom.us/j/999',
        ]);
    }

    public function test_waktu_selesai_task_terisi_otomatis(): void
    {
        $task = WeeklyTask::where('status', 'todo')
            ->whereHas('internship', fn ($q) => $q->where('status', 'berjalan'))
            ->firstOrFail();

        $this->assertNull($task->completed_at);

        $task->update(['status' => 'completed']);
        $this->assertNotNull($task->fresh()->completed_at);

        $task->update(['status' => 'in_progress']);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_template_task_bisa_disalin_ke_penempatan(): void
    {
        $template = TaskTemplate::create(['owner_id' => $this->user('supervisor@kp.test')->id, 'name' => 'Onboarding', 'is_shared' => true]);
        foreach ([[1, 'senin', 'Siapkan lingkungan kerja'], [1, 'jumat', 'Rangkuman minggu pertama'], [2, 'senin', 'Perbaikan kecil pertama']] as $i => [$week, $day, $title]) {
            $template->items()->create(['week_number' => $week, 'day' => $day, 'title' => $title, 'sort_order' => $i]);
        }
        $fajar = $this->activeInternship('fajar@kp.test');
        $sebelum = $fajar->tasks()->count();

        $jumlah = $template->applyTo($fajar, $fajar->student_id);
        $copied = $fajar->tasks()->where('title', 'Rangkuman minggu pertama')->firstOrFail();
        $this->assertSame(1, $copied->week_number);
        $this->assertSame($fajar->start_date->copy()->addDays(4)->toDateString(), $copied->due_date->toDateString());
        $this->assertNotNull($copied->board_column_id);

        $this->assertSame($template->items()->count(), $jumlah);
        $this->assertSame($sebelum + $jumlah, $fajar->tasks()->count());
    }

    // ---------- Penilaian ----------

    public function test_huruf_mutu_mengikuti_skala_nilai(): void
    {
        $this->assertSame('A', Grading::letterFor(88));
        $this->assertSame('B+', Grading::letterFor(87.99));
        $this->assertSame('B', Grading::letterFor(70));
        $this->assertSame('C', Grading::letterFor(56));
        $this->assertSame('D', Grading::letterFor(40));
        $this->assertSame('E', Grading::letterFor(39.9));
    }

    public function test_nilai_akhir_dihitung_dari_bobot_rubrik(): void
    {
        $assessment = Assessment::firstOrFail();

        // 90, 85, 92, 88 dengan bobot 25% masing-masing
        $this->assertSame(88.75, $assessment->computeFinalScore());
        $this->assertSame('A', $assessment->letter_grade);
        $this->assertSame('validated', $assessment->status);
    }

    public function test_penilaian_tervalidasi_tidak_bisa_diubah(): void
    {
        $assessment = Assessment::firstOrFail();

        $this->expectException(LogicException::class);

        $assessment->update(['final_score' => 99]);
    }

    public function test_skor_aspek_pada_penilaian_tervalidasi_tidak_bisa_diubah(): void
    {
        $score = Assessment::firstOrFail()->scores()->firstOrFail();

        $this->expectException(LogicException::class);

        $score->update(['score' => 10]);
    }

    public function test_perubahan_skala_tidak_mengubah_huruf_mutu_lama(): void
    {
        $assessment = Assessment::firstOrFail();
        $versiLama = $assessment->grade_scale_version;

        $versiBaru = Grading::publishScale(['A' => 95, 'B' => 80, 'C' => 60, 'D' => 40, 'E' => 0]);

        $this->assertGreaterThan($versiLama, $versiBaru);
        $this->assertSame('A', $assessment->fresh()->letter_grade);
        $this->assertSame('B', Grading::letterFor(88.75, $versiBaru));
        $this->assertSame('A', Grading::letterFor(88.75, $versiLama));
    }

    public function test_nilai_sidang_mendapat_huruf_mutu_saat_disimpan(): void
    {
        $defense = Internship::where('status', 'selesai')->firstOrFail()->defenseScore;

        $this->assertSame('B+', $defense->letter_grade);
        $this->assertNotNull($defense->grade_scale_version);
    }

    // ---------- Konversi dan sertifikat ----------

    public function test_perubahan_status_konversi_tercatat_di_riwayat(): void
    {
        $candidate = ConversionCandidate::firstOrFail();

        $this->assertSame('ditawari', $candidate->status);

        $candidate->moveTo('diterima', $this->user('supervisor2@kp.test')->id, 'Menerima tawaran.');

        $this->assertSame('diterima', $candidate->fresh()->status);
        $this->assertSame(3, $candidate->histories()->count());
    }

    public function test_sertifikat_punya_nomor_dan_token_verifikasi_unik(): void
    {
        $sertifikat = Certificate::firstOrFail();

        $this->assertMatchesRegularExpression('#^KP/\d{4}/\d{4}$#', $sertifikat->number);
        $this->assertGreaterThanOrEqual(40, strlen($sertifikat->verification_token));
        $this->assertTrue($sertifikat->isValid());
        $this->assertNotSame($sertifikat->number, Certificate::nextNumber());
    }

    // ---------- KP mandiri ----------

    public function test_kp_mandiri_menunggu_dua_tahap_persetujuan(): void
    {
        $nadia = Internship::where('student_id', $this->user('nadia@kp.test')->id)->firstOrFail();

        $this->assertSame('diajukan', $nadia->status);
        $this->assertSame(2, $nadia->approvals()->where('status', 'pending')->count());
        $this->assertSame('yusuf@karyamandiri.test', $nadia->supervisor_email);
        $this->assertSame(0, $nadia->invitations()->count(), 'Undangan diterbitkan TU setelah disetujui');
    }
}
