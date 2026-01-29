<?php

namespace App\Policies;

use App\Models\Scrap;
use App\Models\User;

class ScrapPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Scrap $scrap): bool
    {
        return $user->organization_id === $scrap->organization_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Scrap $scrap): bool
    {
        return $user->organization_id === $scrap->organization_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Scrap $scrap): bool
    {
        return $user->organization_id === $scrap->organization_id;
    }
}
