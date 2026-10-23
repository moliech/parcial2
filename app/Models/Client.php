<?php

 namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'document_number',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address'
    ];

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }
}
