<?php

namespace App\Policies;

use App\Models\OpeningHour;
use App\Models\User;

class OpeningHourPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Public API - anyone can view opening hours
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, OpeningHour $openingHour): bool
    {
        // Public API - anyone can view individual opening hours
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only authenticated users can create opening hours
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OpeningHour $openingHour): bool
    {
        // Only authenticated users can update opening hours
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OpeningHour $openingHour): bool
    {
        // Only authenticated users can delete opening hours
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OpeningHour $openingHour): bool
    {
        // Only admins can restore opening hours
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OpeningHour $openingHour): bool
    {
        // Only admins can permanently delete opening hours
        return $user->isAdmin();
    }
}
