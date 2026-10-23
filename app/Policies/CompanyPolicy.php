<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    /** Daftar perusahaan boleh dilihat biro dan kaprodi. */
    public function viewAny(User $user): Response
    {
        return $user->hasRole('biro', 'kaprodi')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke data perusahaan.');
    }

    /** Hanya biro kerjasama yang menambah dan mengubah data perusahaan. */
    public function manage(User $user): Response
    {
        return $user->hasRole('biro')
            ? Response::allow()
            : Response::deny('Hanya Biro Kerjasama yang dapat mengelola perusahaan.');
    }

    /** Statistik satu perusahaan: biro, kaprodi, dan staf perusahaan itu sendiri. */
    public function viewStats(User $user, Company $company): Response
    {
        if ($user->hasRole('biro', 'kaprodi')) {
            return Response::allow();
        }

        $isStaff = $user->hasRole('supervisor')
            && $user->staffProfile()->where('company_id', $company->id)->exists();

        return $isStaff
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke statistik perusahaan ini.');
    }
}
