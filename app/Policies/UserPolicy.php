<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /** Data mahasiswa dikelola kaprodi. */
    public function manageStudents(User $user): Response
    {
        return $user->hasRole('kaprodi')
            ? Response::allow()
            : Response::deny('Hanya Kaprodi yang dapat mengelola data mahasiswa.');
    }
}
