<?php

namespace App\Models;

use App\Domain\Employee\Models\Employment;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employment_id',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHrManager(): bool
    {
        return $this->role === 'hr_manager';
    }

    public function isPeopleManager(): bool
    {
        return $this->role === 'people_manager';
    }

    public function hasBackOfficeAccess(): bool
    {
        return in_array($this->role, ['admin', 'hr_manager'], true);
    }

    /**
     * This is a JSON-only API with no server-rendered "password.reset" route,
     * so point the reset link at the frontend SPA instead of Laravel's default.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
