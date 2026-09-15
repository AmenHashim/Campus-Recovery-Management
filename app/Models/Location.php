<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active', 'latitude', 'longitude'];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /** True when this location has coordinates the report form can pre-pin. */
    public function hasPin(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** How many items reference this location by name. */
    public function itemCount(): int
    {
        return Item::where('location', $this->name)->count();
    }
}
