<?php

namespace App\Actions\Companies;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** TU Fakultas menambah perusahaan mitra baru, atau mengubah data perusahaan yang ada. */
class SaveCompany
{
    /**
     * @param  array<string, mixed>  $data  sudah divalidasi oleh SaveCompanyRequest
     */
    public function handle(User $actor, ?Company $company, array $data): Company
    {
        return DB::transaction(function () use ($actor, $company, $data) {
            $fields = [
                'name' => $data['name'],
                'industry' => $data['industry'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
            ];

            if ($company) {
                $company->update($fields);
                $action = 'perusahaan.ubah';
                $verb = 'Mengubah';
            } else {
                $company = Company::create($fields + [
                    'status' => 'mitra',
                    'created_by' => $actor->id,
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
                $action = 'perusahaan.tambah';
                $verb = 'Menambah';
            }

            // Divisi diisi satu per baris. Divisi yang sudah dipakai penempatan tidak pernah dihapus.
            foreach ($this->divisionNames($data['divisions'] ?? null) as $name) {
                $company->divisions()->firstOrCreate(['name' => $name]);
            }

            ActivityLog::record($action, "{$verb} perusahaan {$company->name}", $company);

            return $company;
        });
    }

    /** @return list<string> */
    private function divisionNames(?string $raw): array
    {
        if (! filled($raw)) {
            return [];
        }

        $names = array_map(fn ($line) => mb_substr(trim($line), 0, 100), preg_split('/\R/', $raw));

        return array_values(array_unique(array_filter($names, fn ($name) => $name !== '')));
    }
}
