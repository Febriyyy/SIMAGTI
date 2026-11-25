<?php

namespace App\Actions\Students;

use App\Models\ActivityLog;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Impor banyak mahasiswa sekaligus dari Excel/CSV (mis. hasil ekspor SIAKAD).
 * Kolom: nama, email, nim, kode_prodi, angkatan, ipk. Kaprodi berprodi hanya boleh mengimpor ke prodinya
 * (kode_prodi boleh dikosongkan). Setiap akun mendapat kata sandi sementara yang wajib diganti saat masuk.
 */
class ImportStudents
{
    public const COLUMNS = ['nama', 'email', 'nim', 'kode_prodi', 'angkatan', 'ipk'];

    public const MAX_ROWS = 1000;

    /**
     * Periksa setiap baris tanpa menyimpan apa pun.
     *
     * @param  list<array{line: int, data: array<string, string>}>  $rows
     * @return array{valid: list<array<string, mixed>>, invalid: list<array{line: int, name: string, errors: list<string>}>}
     */
    public function check(User $actor, array $rows): array
    {
        $programs = StudyProgram::all()->keyBy(fn ($p) => mb_strtoupper($p->code));
        $ownProgram = $actor->programId();

        $emails = array_map(fn ($r) => mb_strtolower($r['data']['email'] ?? ''), $rows);
        $nims = array_map(fn ($r) => $r['data']['nim'] ?? '', $rows);
        $takenEmails = User::whereIn('email', array_filter($emails))->pluck('email')->map(fn ($e) => mb_strtolower($e))->flip();
        $takenNims = User::whereIn('nim', array_filter($nims))->pluck('nim')->flip();
        $emailCount = array_count_values(array_filter($emails));
        $nimCount = array_count_values(array_filter($nims));

        $valid = [];
        $invalid = [];
        foreach ($rows as $row) {
            $d = $row['data'];
            $errors = [];
            $name = trim($d['nama'] ?? '');
            $email = mb_strtolower(trim($d['email'] ?? ''));
            $nim = preg_replace('/\s+/', '', (string) ($d['nim'] ?? ''));
            $code = mb_strtoupper(trim($d['kode_prodi'] ?? ''));
            $year = trim($d['angkatan'] ?? '');
            $gpa = str_replace(',', '.', trim($d['ipk'] ?? ''));

            if ($name === '' || mb_strlen($name) > 100) {
                $errors[] = 'Nama wajib diisi (maks. 100 karakter).';
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email tidak valid.';
            } elseif (isset($takenEmails[$email])) {
                $errors[] = 'Email sudah terdaftar.';
            } elseif (($emailCount[$email] ?? 0) > 1) {
                $errors[] = 'Email muncul lebih dari sekali di berkas.';
            }
            if ($nim === '' || mb_strlen($nim) > 20) {
                $errors[] = 'NIM wajib diisi (maks. 20 karakter).';
            } elseif (isset($takenNims[$nim])) {
                $errors[] = 'NIM sudah terdaftar.';
            } elseif (($nimCount[$nim] ?? 0) > 1) {
                $errors[] = 'NIM muncul lebih dari sekali di berkas.';
            }

            $program = $code === '' ? ($ownProgram ? $programs->firstWhere('id', $ownProgram) : null) : $programs->get($code);
            if (! $program) {
                $errors[] = $code === '' ? 'Kode prodi wajib diisi.' : "Kode prodi {$code} tidak dikenal.";
            } elseif (! $actor->managesProgram($program->id)) {
                $errors[] = 'Hanya boleh mahasiswa prodi Anda sendiri.';
            }
            if ($year !== '' && (! ctype_digit($year) || (int) $year < 2000 || (int) $year > (int) date('Y') + 1)) {
                $errors[] = 'Angkatan tidak valid.';
            }
            if ($gpa !== '' && (! is_numeric($gpa) || (float) $gpa < 0 || (float) $gpa > 4)) {
                $errors[] = 'IPK harus 0–4.';
            }

            if ($errors) {
                $invalid[] = ['line' => $row['line'], 'name' => $name ?: ($email ?: '–'), 'errors' => $errors];
            } else {
                $valid[] = [
                    'line' => $row['line'], 'name' => $name, 'email' => $email, 'nim' => $nim,
                    'study_program_id' => $program->id, 'program' => $program->name,
                    'entry_year' => $year !== '' ? (int) $year : null, 'gpa' => $gpa !== '' ? round((float) $gpa, 2) : null,
                ];
            }
        }

        return compact('valid', 'invalid');
    }

    /**
     * Simpan baris yang sudah lolos pemeriksaan. Baris yang ternyata bentrok saat disimpan dilewati.
     *
     * @param  list<array<string, mixed>>  $valid
     * @return array{created: list<array{name: string, nim: string, email: string, program: string, password: string}>, skipped: int}
     */
    public function import(User $actor, array $valid, CreateStudent $create): array
    {
        $created = [];
        $skipped = 0;

        DB::transaction(function () use ($actor, $valid, $create, &$created, &$skipped) {
            foreach ($valid as $row) {
                if (! $actor->managesProgram((int) $row['study_program_id'])
                    || User::where('email', $row['email'])->orWhere('nim', $row['nim'])->exists()) {
                    $skipped++;

                    continue;
                }
                $password = Str::password(10, symbols: false);
                $create->handle($actor, [
                    'name' => $row['name'], 'email' => $row['email'], 'nim' => $row['nim'],
                    'study_program_id' => $row['study_program_id'], 'entry_year' => $row['entry_year'],
                    'gpa' => $row['gpa'], 'password' => $password, 'must_change_password' => true,
                ], log: false);
                $created[] = ['name' => $row['name'], 'nim' => $row['nim'], 'email' => $row['email'], 'program' => $row['program'], 'password' => $password];
            }

            ActivityLog::record('mahasiswa.impor', 'Mengimpor '.count($created).' mahasiswa dari berkas', null);
        });

        return compact('created', 'skipped');
    }
}
