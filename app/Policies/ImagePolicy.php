<?php

namespace App\Policies;

use App\Models\Image;
use App\Models\User;

class ImagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Public API - anyone can view images
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Image $image): bool
    {
        // Public API - anyone can view individual images
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only authenticated users can create images
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Image $image): bool
    {
        // Only authenticated users can update images
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Image $image): bool
    {
        // Only authenticated users can delete images
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Image $image): bool
    {
        // Only admins can restore images
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Image $image): bool
    {
        // Only admins can permanently delete images
        return $user->isAdmin();
    }
}
