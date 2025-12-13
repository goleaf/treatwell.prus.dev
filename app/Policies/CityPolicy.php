<?php

namespace App\Policies;

use App\Models\City;
use App\Models\User;

class CityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Public API - anyone can view cities
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, City $city): bool
    {
        // Public API - anyone can view individual cities
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins can create cities
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, City $city): bool
    {
        // Only admins can update cities
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, City $city): bool
    {
        // Only admins can delete cities
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, City $city): bool
    {
        // Only admins can restore cities
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, City $city): bool
    {
        // Only admins can permanently delete cities
        return $user->isAdmin();
    }
}
