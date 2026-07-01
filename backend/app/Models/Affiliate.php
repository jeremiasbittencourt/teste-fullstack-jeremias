<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    /** @use HasFactory<\Database\Factories\AffiliateFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'email',
        'username',
        'phone',
        'city',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
