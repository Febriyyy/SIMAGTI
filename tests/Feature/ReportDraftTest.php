<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDraftTest extends TestCase
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

    public function test_mahasiswa_mendapat_draf_laporan_dari_data_kp(): void
    {
        $student = $this->user('mahasiswa@kp.test');
        $internship = Internship::where('student_id', $student->id)->latest('id')->firstOrFail();
        $plan = $internship->activityPlans()->firstOrFail();
        $entry = $internship->timeEntries()->latest('work_date')->firstOrFail();

        $this->actingAs($student)->get('/timesheet')->assertOk()->assertSee(route('laporan.draf'));

        $this->get('/laporan')->assertOk()
            ->assertSee('Laporan Kerja Praktik')
            ->assertSee($student->name)
            ->assertSee($internship->placeName())
            ->assertSee($plan->title)
            ->assertSee($entry->description)
            ->assertSee('Uraian Kegiatan Mingguan')
            ->assertSee('Salin teks')
            ->assertSee('Tulis latar belakang');
    }

    public function test_hanya_mahasiswa_dengan_kp_yang_bisa_membuka_draf(): void
    {
        $this->actingAs($this->user('dospem@kp.test'))->get('/laporan')->assertForbidden();
    }
}
