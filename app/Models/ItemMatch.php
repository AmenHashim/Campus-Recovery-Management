<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMatch extends Model
{
    protected $table = 'item_matches';

    protected $fillable = [
        'lost_item_id', 'found_item_id', 'confidence_score', 'match_status', 'reviewed_by',
    ];

    protected $casts = ['confidence_score' => 'float'];

    public function lostItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'lost_item_id');
    }

    public function foundItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'found_item_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }
}
