<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    protected $fillable = [
        'item_id', 'claimant_user_id', 'token', 'status', 'verified_by', 'verified_at', 'notes',
    ];

    protected $casts = ['verified_at' => 'datetime'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_user_id')->withTrashed();
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by')->withTrashed();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'verified' => 'badge-found',
            'rejected' => 'badge-lost',
            default => 'badge-pending',
        };
    }
}
