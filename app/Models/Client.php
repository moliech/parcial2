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

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
