<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'manager_id',
        'is_active',
        'notify_email',
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
            'notify_email' => 'boolean',
        ];
    }

    public function manager()
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function directReports()
    {
        return $this->hasMany(self::class, 'manager_id');
    }
}
