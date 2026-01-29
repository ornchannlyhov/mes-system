<?php

namespace App\Policies;

use App\Models\UnbuildOrder;
use App\Models\User;

class UnbuildOrderPolicy
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
    public function view(User $user, UnbuildOrder $unbuildOrder): bool
    {
        return $user->organization_id === $unbuildOrder->organization_id;
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
    public function update(User $user, UnbuildOrder $unbuildOrder): bool
    {
        return $user->organization_id === $unbuildOrder->organization_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UnbuildOrder $unbuildOrder): bool
    {
        return $user->organization_id === $unbuildOrder->organization_id;
    }
}
