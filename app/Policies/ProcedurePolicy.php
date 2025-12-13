<?php

namespace App\Policies;

use App\Models\Procedure;
use App\Models\User;

class ProcedurePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Public API - anyone can view procedures
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Procedure $procedure): bool
    {
        // Public API - anyone can view individual procedures
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins can create procedures
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Procedure $procedure): bool
    {
        // Only admins can update procedures
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Procedure $procedure): bool
    {
        // Only admins can delete procedures
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Procedure $procedure): bool
    {
        // Only admins can restore procedures
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Procedure $procedure): bool
    {
        // Only admins can permanently delete procedures
        return $user->isAdmin();
    }
}
