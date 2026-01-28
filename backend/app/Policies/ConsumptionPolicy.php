<?php

namespace App\Policies;

use App\Models\Consumption;
use App\Models\User;

class ConsumptionPolicy
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
    public function view(User $user, Consumption $consumption): bool
    {
        return $user->organization_id === $consumption->manufacturingOrder->organization_id;
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
    public function update(User $user, Consumption $consumption): bool
    {
        return $user->organization_id === $consumption->manufacturingOrder->organization_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Consumption $consumption): bool
    {
        return $user->organization_id === $consumption->manufacturingOrder->organization_id;
    }
}
