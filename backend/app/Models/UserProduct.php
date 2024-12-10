<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'ownership_type',
        'rent_expires_at',
        'unique_code',
        'rent_started_at',
    ];

    protected $dates = [
        'rent_expires_at',
    ];

    protected $casts = [
        'rent_started_at' => 'datetime',
        'rent_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
