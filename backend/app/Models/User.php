<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function userProducts()
    {
        return $this->hasMany(UserProduct::class);
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:2',
    ];
}
