<?php

namespace App\Policies;

use App\Domain\Employee\Models\Employment;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // scoping to "own team" vs "everyone" happens in the controller query.
    }

    public function view(User $user, Employment $employment): bool
    {
        if ($user->hasBackOfficeAccess()) {
            return true;
        }

        if ($user->role === 'people_manager') {
            return $this->managesEmployment($user, $employment);
        }

        return $user->employment_id === $employment->id;
    }

    public function create(User $user): bool
    {
        return $user->hasBackOfficeAccess();
    }

    public function update(User $user, Employment $employment): bool
    {
        return $user->hasBackOfficeAccess();
    }

    public function transfer(User $user, Employment $employment): bool
    {
        return $user->hasBackOfficeAccess();
    }

    private function managesEmployment(User $user, Employment $employment): bool
    {
        $current = $employment->currentAssignment()->first();

        return $current && $user->employment_id === $current->manager_employment_id;
    }
}
