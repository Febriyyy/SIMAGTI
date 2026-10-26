<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    /** Daftar perusahaan boleh dilihat TU dan kaprodi. */
    public function viewAny(User $user): Response
    {
        return $user->hasRole('tu', 'kaprodi')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke data perusahaan.');
    }

    /** Hanya TU Fakultas yang menambah dan mengubah data perusahaan. */
    public function manage(User $user): Response
    {
        return $user->hasRole('tu')
            ? Response::allow()
            : Response::deny('Hanya TU Fakultas yang dapat mengelola perusahaan.');
    }

    /** Statistik satu perusahaan: TU, kaprodi, dan staf perusahaan itu sendiri. */
    public function viewStats(User $user, Company $company): Response
    {
        if ($user->hasRole('tu', 'kaprodi')) {
            return Response::allow();
        }

        $isStaff = $user->hasRole('supervisor')
            && $user->staffProfile()->where('company_id', $company->id)->exists();

        return $isStaff
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke statistik perusahaan ini.');
    }
}
