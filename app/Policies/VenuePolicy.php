<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Public API - anyone can view venues
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Venue $venue): bool
    {
        // Public API - anyone can view individual venues
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only authenticated users can create venues
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Venue $venue): bool
    {
        // Only authenticated users can update venues
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Venue $venue): bool
    {
        // Only authenticated users can delete venues
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Venue $venue): bool
    {
        // Only admins can restore venues
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Venue $venue): bool
    {
        // Only admins can permanently delete venues
        return $user->isAdmin();
    }
}
