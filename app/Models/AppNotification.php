<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function icon(): string
    {
        return match ($this->type) {
            'match_found' => 'ti-target-arrow',
            'claim_submitted' => 'ti-ticket',
            'claim_verified' => 'ti-circle-check',
            'claim_rejected' => 'ti-circle-x',
            default => 'ti-bell',
        };
    }
}
