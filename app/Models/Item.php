<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'guest_reporter_id', 'filed_by',
        'type', 'name', 'category', 'location', 'latitude', 'longitude', 'date',
        'description', 'contact', 'image', 'status',
    ];

    protected $casts = [
        'date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /** True when the reporter dropped a map pin rather than only naming a place. */
    public function hasPin(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** withTrashed: a deleted account must not blank out the history it created. */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function guestReporter(): BelongsTo
    {
        return $this->belongsTo(GuestReporter::class);
    }

    public function filedByStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by')->withTrashed();
    }

    public function matchesAsLost(): HasMany
    {
        return $this->hasMany(ItemMatch::class, 'lost_item_id');
    }

    public function matchesAsFound(): HasMany
    {
        return $this->hasMany(ItemMatch::class, 'found_item_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function scopeLost(Builder $q): Builder
    {
        return $q->where('type', 'lost');
    }

    public function scopeFound(Builder $q): Builder
    {
        return $q->where('type', 'found');
    }

    public function reporterName(): string
    {
        return $this->reporter?->name ?? $this->guestReporter?->name ?? 'Unknown';
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function isLost(): bool
    {
        return $this->type === 'lost';
    }

    public function isFound(): bool
    {
        return $this->type === 'found';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'open' => 'badge-pending',
            'matched' => 'badge-found',
            default => 'badge-claimed', // claimed, returned, closed
        };
    }

    public function categoryIcon(): string
    {
        return self::categoryIconMap()[$this->category] ?? 'ti-box';
    }

    /**
     * Category name → icon, sourced from the admin-managed categories (FR-F2) with a
     * hardcoded fallback for the seeded set. Memoised per request so rendering a page of
     * items costs a single query, not one per item.
     *
     * @return array<string, string>
     */
    protected static function categoryIconMap(): array
    {
        static $map = null;

        if ($map === null) {
            $fallback = [
                'Electronics' => 'ti-device-mobile',
                'Documents' => 'ti-file-text',
                'Clothing' => 'ti-shirt',
                'Bags' => 'ti-backpack',
                'Keys' => 'ti-key',
                'Accessories' => 'ti-watch',
                'Books' => 'ti-book',
            ];

            $managed = Category::whereNotNull('icon')->pluck('icon', 'name')->all();

            $map = array_merge($fallback, $managed);
        }

        return $map;
    }
}
