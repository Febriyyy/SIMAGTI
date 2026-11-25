<?php

namespace Tests\Feature;

use App\Models\LecturerProfile;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\SheetReader;
use App\Support\SimpleXlsx;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Impor mahasiswa dari Excel/CSV: pratinjau dulu, simpan baris valid, kata sandi sementara diunduh sekali. */
class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    private function kaprodi(): User
    {
        return User::where('email', 'kaprodi@kp.test')->firstOrFail();
    }

    private function csv(string $content, string $name = 'mahasiswa.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    public function test_impor_csv_pratinjau_lalu_simpan_dan_unduh_kata_sandi_sekali(): void
    {
        $kaprodi = $this->kaprodi();
        $this->actingAs($kaprodi)->get('/mahasiswa')->assertSee(route('mahasiswa.impor'));

        // Titik koma (CSV Excel versi Indonesia), judul kolom bebas huruf besar dan spasi, IPK berkoma
        $file = $this->csv("Nama;Email;NIM;Kode Prodi;Angkatan;IPK\n"
            ."Andi Saputra;Andi@Mhs.test;2401001;TIF;2024;3,45\n"
            ."Budi Lestari;budi@mhs.test;2401002;SI;2024;\n"
            ."\n"
            ."Tanpa Email;;2401003;TIF;;\n"
            ."Ganda;dospem@kp.test;2401004;TIF;;\n"
            ."Prodi Asing;asing@mhs.test;2401005;XYZ;;\n");

        $this->post('/mahasiswa/impor', ['berkas' => $file])->assertRedirect('/mahasiswa/impor');
        $this->assertNull(User::where('email', 'andi@mhs.test')->first(), 'Pratinjau belum menyimpan apa pun');

        $this->get('/mahasiswa/impor')->assertOk()
            ->assertSee('2 siap')->assertSee('3 bermasalah')
            ->assertSee('Email tidak valid.')->assertSee('Email sudah terdaftar.')->assertSee('Kode prodi XYZ tidak dikenal.')
            ->assertSee('Simpan 2 mahasiswa');

        $this->post('/mahasiswa/impor/simpan')->assertRedirect('/mahasiswa/impor');
        $andi = User::where('email', 'andi@mhs.test')->firstOrFail();
        $this->assertSame('mahasiswa', $andi->role);
        $this->assertSame('2401001', $andi->nim);
        $this->assertTrue((bool) $andi->must_change_password);
        $this->assertSame('TIF', $andi->studentProfile->studyProgram->code);
        $this->assertEquals(3.45, (float) $andi->studentProfile->gpa);
        $this->assertSame(2024, (int) $andi->studentProfile->entry_year);
        $this->assertSame('SI', User::where('email', 'budi@mhs.test')->firstOrFail()->studentProfile->studyProgram->code);

        $this->get('/mahasiswa/impor')->assertSee('2 mahasiswa ditambahkan')->assertSee('Unduh daftar akun');

        // Daftar kata sandi: berkas Excel, hanya sekali
        $download = $this->get('/mahasiswa/impor/akun');
        $download->assertOk();
        $rows = SheetReader::read($download->getFile()->getPathname(), 'xlsx');
        $this->assertSame('andi@mhs.test', collect($rows)->firstWhere('data.nim', '2401001')['data']['email'] ?? null);
        $this->get('/mahasiswa/impor/akun')->assertRedirect('/mahasiswa/impor');

        // Kata sandi sementara berfungsi
        $password = collect($rows)->firstWhere('data.nim', '2401001')['data']['kata_sandi_sementara'];
        auth()->logout();
        $this->post('/login', ['email' => 'andi@mhs.test', 'password' => $password])->assertRedirect();
    }

    public function test_impor_dari_template_excel(): void
    {
        $kaprodi = $this->kaprodi();
        $this->actingAs($kaprodi)->get('/mahasiswa/impor/template')->assertOk();

        $path = (new SimpleXlsx)->addSheet('Mahasiswa', [
            ['nama', 'email', 'nim', 'kode_prodi', 'angkatan', 'ipk'],
            ['Citra Dewi', 'citra@mhs.test', '2401010', 'SI', 2023, 3.8],
            ['Dimas Putra', 'dimas@mhs.test', '2401011', 'TIF', 2023, 3.1],
        ])->save();
        $file = new UploadedFile($path, 'mahasiswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->post('/mahasiswa/impor', ['berkas' => $file])->assertRedirect('/mahasiswa/impor');
        $this->get('/mahasiswa/impor')->assertSee('2 siap')->assertDontSee('bermasalah');
        $this->post('/mahasiswa/impor/simpan');
        $this->assertSame(3.8, (float) User::where('nim', '2401010')->firstOrFail()->studentProfile->gpa);
    }

    public function test_nim_ganda_dalam_berkas_dan_kolom_wajib(): void
    {
        $this->actingAs($this->kaprodi());

        $this->post('/mahasiswa/impor', ['berkas' => $this->csv("nama,email\nA,a@mhs.test\n")])
            ->assertSessionHasErrors('berkas');

        $this->post('/mahasiswa/impor', ['berkas' => $this->csv("nama,email,nim,kode_prodi\nA,a@mhs.test,999,TIF\nB,b@mhs.test,999,TIF\n")]);
        $this->get('/mahasiswa/impor')->assertSee('NIM muncul lebih dari sekali di berkas.')->assertDontSee('Simpan');
    }

    public function test_kaprodi_berprodi_hanya_mengimpor_ke_prodinya(): void
    {
        $si = StudyProgram::where('code', 'SI')->firstOrFail();
        $kaprodiSi = User::create(['name' => 'Kaprodi SI', 'email' => 'kaprodi.si@kp.test', 'password' => 'rahasia123', 'role' => 'kaprodi', 'is_active' => true]);
        LecturerProfile::create(['user_id' => $kaprodiSi->id, 'study_program_id' => $si->id]);

        // kode_prodi kosong = prodi kaprodi sendiri; prodi lain ditolak
        $this->actingAs($kaprodiSi->fresh())->post('/mahasiswa/impor', ['berkas' => $this->csv(
            "nama,email,nim,kode_prodi\nEka,eka@mhs.test,2401020,\nFaris,faris@mhs.test,2401021,TIF\n"
        )]);
        $this->get('/mahasiswa/impor')->assertSee('1 siap')->assertSee('Hanya boleh mahasiswa prodi Anda sendiri.');
        $this->post('/mahasiswa/impor/simpan');
        $this->assertSame($si->id, User::where('nim', '2401020')->firstOrFail()->studentProfile->study_program_id);
        $this->assertNull(User::where('nim', '2401021')->first());
    }

    public function test_hanya_kaprodi_yang_bisa_mengimpor(): void
    {
        foreach (['tu@kp.test', 'dospem@kp.test', 'admin@kp.test'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail())->get('/mahasiswa/impor')->assertForbidden();
        }
    }
}
