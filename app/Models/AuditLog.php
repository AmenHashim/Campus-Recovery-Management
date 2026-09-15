<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * A single immutable audit entry (FR-F4). Never updated after creation — only
 * created_at is tracked. Write entries through AuditLog::record().
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // immutable: no updated_at column

    protected $fillable = ['user_id', 'action', 'description', 'subject_type', 'subject_id', 'ip_address'];

    /** Which action prefixes make up each viewer filter group. */
    public const GROUPS = [
        'auth' => ['auth'],
        'items' => ['item'],
        'claims' => ['claim'],
        'users' => ['user'],
        'reference' => ['category', 'location'],
    ];

    /**
     * Record an audit entry. Actor and IP are taken from the current request unless an
     * explicit $actorId is passed (e.g. from a seeder or a queued job with no session).
     */
    public static function record(string $action, string $description, ?Model $subject = null, ?int $actorId = null): self
    {
        return static::create([
            'user_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()->ip(),
        ]);
    }

    /** withTrashed: the trail must still name an actor whose account was deleted. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Restrict to the action prefixes belonging to a named viewer group. */
    public function scopeInGroup(Builder $q, string $group): Builder
    {
        $prefixes = self::GROUPS[$group] ?? [];

        return $q->where(function (Builder $q) use ($prefixes) {
            foreach ($prefixes as $prefix) {
                $q->orWhere('action', 'like', $prefix.'.%');
            }
        });
    }

    public function actorName(): string
    {
        return $this->user?->name ?? 'System';
    }

    /** The action prefix before the dot — used to colour/group entries. */
    public function actionGroup(): string
    {
        return explode('.', $this->action)[0];
    }

    public function icon(): string
    {
        return match ($this->actionGroup()) {
            'auth' => 'ti-login',
            'item' => 'ti-box',
            'claim' => 'ti-ticket',
            'user' => 'ti-user-cog',
            'category', 'location' => 'ti-category',
            default => 'ti-point',
        };
    }
}
