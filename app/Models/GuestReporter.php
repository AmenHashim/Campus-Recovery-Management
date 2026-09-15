<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestReporter extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'id_type', 'id_number'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
