<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use App\Support\Contribution;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContributionTest extends TestCase
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

    public function test_peta_kontribusi_menghitung_catatan_dan_task_selesai_per_hari(): void
    {
        $internship = $this->internshipOf('mahasiswa@kp.test');
        $monday = Carbon::today()->startOfWeek(Carbon::MONDAY);
        $internship->update(['start_date' => $monday->copy()->subWeeks(2), 'end_date' => $monday->copy()->addWeeks(4)]);
        $internship->timeEntries()->delete();
        $internship->tasks()->update(['completed_at' => null]);

        $student = $this->user('mahasiswa@kp.test');
        foreach ([1, 2] as $back) {
            $internship->timeEntries()->create(['work_date' => $monday->copy()->subWeek()->addDays($back - 1), 'description' => 'Catatan', 'created_by' => $student->id]);
        }
        $internship->tasks()->first()->forceFill(['completed_at' => $monday->copy()->subWeek()->setTime(10, 0)])->saveQuietly();

        $data = Contribution::build($internship->fresh(), $monday->copy()->addDays(2));
        $cells = collect($data['weeks'])->flatten(1)->filter();

        $this->assertSame(2, $cells->first(fn ($d) => $d['date']->isSameDay($monday->copy()->subWeek()))['count'], 'Catatan + task selesai di hari yang sama dijumlah');
        $this->assertSame(3, $data['total']);
        $this->assertSame(2, $data['activeDays']);
        $this->assertSame(2, $data['longest']);
        $this->assertTrue($cells->last()['future']);
    }

    public function test_peta_kontribusi_tidak_lagi_tampil(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')->assertOk()->assertDontSee('Peta kontribusi');

        $internship = $this->internshipOf('mahasiswa@kp.test');
        $this->actingAs($this->user('dospem@kp.test'))->get("/pantau/{$internship->id}?tab=aktivitas")->assertOk()->assertDontSee('Peta kontribusi');
    }
}
