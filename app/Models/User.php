<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use CrudTrait;
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        // This would typically check a role or permission
        // For now, we'll use a simple email check
        return in_array($this->email, [
            'admin@example.com',
            'admin@treatwell.com',
        ]);
    }

    /**
     * Get user's display name.
     */
    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Get user's initials.
     */
    public function getInitials(): string
    {
        $names = explode(' ', $this->name ?? '');
        $initials = '';

        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return $initials ?: strtoupper(substr($this->email, 0, 2));
    }
}
