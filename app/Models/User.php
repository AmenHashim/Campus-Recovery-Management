<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /**
     * SoftDeletes — a "deleted" account is hidden everywhere (login included, since
     * the auth guard queries this model) but its row and all its history survive.
     * Only an admin deletes accounts; the deletion is reversible via restore().
     */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /* ─────────────────────────────────────────────────────────────
     | ROLES (owned by spatie/laravel-permission)
     | Use these constants everywhere — never raw strings. Renaming a
     | role then means changing one line here + one seeder line.
     ───────────────────────────────────────────────────────────── */
    public const ROLE_STUDENT_STAFF = 'student_staff';
    public const ROLE_OFFICER       = 'officer';
    public const ROLE_ADMIN   = 'admin';

    /* ─────────────────────────────────────────────────────────────
     | USER TYPE (University identity — NOT a permission)
     | Only set for the student_staff role; NULL for officer/super_admin.
     ───────────────────────────────────────────────────────────── */
    public const TYPE_STUDENT = 'student';
    public const TYPE_STAFF   = 'staff';

    /* ── ACCOUNT STATUS ── */
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'reg_no',
        'phone',
        'avatar',
        'reputation_points',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed', // auto-bcrypt on assignment — never call Hash::make() yourself
            'reputation_points'  => 'integer',
        ];
    }

    /* ─────────────── Role helpers (thin wrappers over Spatie) ─────────────── */

    public function isStudentStaff(): bool
    {
        return $this->hasRole(self::ROLE_STUDENT_STAFF);
    }

    public function isOfficer(): bool
    {
        return $this->hasRole(self::ROLE_OFFICER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /* ─────────────── Identity & status helpers ─────────────── */

    public function isStudent(): bool
    {
        return $this->user_type === self::TYPE_STUDENT;
    }

    public function isStaff(): bool
    {
        return $this->user_type === self::TYPE_STAFF;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /** Where this user lands after login (FR-A4). */
    public function homeRoute(): string
    {
        return match (true) {
            $this->isAdmin()      => 'admin.dashboard',
            $this->isOfficer()    => 'officer.dashboard',
            default               => 'student.dashboard',
        };
    }

    /** Human-readable label, e.g. "Student" / "Staff" / "Lost & Found Officer". */
    public function roleLabel(): string
    {
        if ($this->isAdmin()) return 'Admin';
        if ($this->isOfficer())    return 'Lost & Found Officer';

        return $this->isStaff() ? 'Staff' : 'Student';
    }

    public function roleBadgeClass(): string
    {
        return match (true) {
            $this->isAdmin() => 'badge-lost',
            $this->isOfficer() => 'badge-found',
            default => 'badge-claimed',
        };
    }

    /* ─────────────── Avatar ─────────────── */

    /** Public URL of the uploaded avatar, or null to fall back to initials. */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    /** Up to two initials for the fallback avatar (e.g. "Biggie Kasese" → "BK"). */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $initials = collect($parts)->filter()->take(2)->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)));

        return $initials->implode('') ?: '?';
    }

    /* ─────────────── Account emails ───────────────
     | Both overrides exist for one reason: the framework's own notifications render
     | through Laravel's generic mail template. Ours render through the CPRMS shell
     | (resources/views/emails/layout.blade.php) so account mail looks like the system
     | it came from. The links themselves are unchanged.
     */

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /* ─────────────── Relationships ───────────────
     | Uncommented as each later sprint adds the model.
     */

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'claimant_user_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    // public function filedItems(): HasMany { return $this->hasMany(Item::class, 'filed_by'); }
    // public function verifiedClaims(): HasMany { return $this->hasMany(Claim::class, 'verified_by'); }
    // public function reputationLogs(): HasMany { return $this->hasMany(ReputationLog::class); }
}
