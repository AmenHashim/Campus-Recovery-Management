<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * User management (FR-F1) — this is also how Officer/Admin accounts get provisioned
 * (BR-07: they can never self-register), by promoting an existing student/staff account.
 */
class UserController extends Controller
{
    /** role_type select values, "role:user_type" — user_type is "none" for officer/admin. */
    protected const ROLE_TYPE_OPTIONS = [
        'student_staff:student',
        'student_staff:staff',
        'officer:none',
        'admin:none',
    ];

    public function index(Request $request): View
    {
        // "deleted" shows only soft-deleted accounts; every other view hides them.
        $showDeleted = $request->string('status')->toString() === 'deleted';

        $query = User::with('roles')->when($showDeleted, fn ($q) => $q->onlyTrashed());

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('reg_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->string('role')->toString());
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => [User::ROLE_STUDENT_STAFF, User::ROLE_OFFICER, User::ROLE_ADMIN],
            'showDeleted' => $showDeleted,
            'deletedCount' => User::onlyTrashed()->count(),
        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403, "You can't suspend your own account.");

        $newStatus = $user->isActive() ? User::STATUS_SUSPENDED : User::STATUS_ACTIVE;
        $user->update(['status' => $newStatus]);

        $action = $newStatus === User::STATUS_ACTIVE ? 'reactivated' : 'suspended';

        AuditLog::record("user.{$action}", ucfirst($action)." account for {$user->name} ({$user->reg_no})", $user);

        return back()->with('status', "{$user->name} has been {$action}.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403, "You can't change your own role.");

        $validated = $request->validate([
            'role_type' => ['required', 'in:'.implode(',', self::ROLE_TYPE_OPTIONS)],
        ]);

        [$role, $userType] = explode(':', $validated['role_type']);

        $user->update(['user_type' => $userType === 'none' ? null : $userType]);
        $user->syncRoles([$role]);

        $label = $user->fresh()->roleLabel();
        AuditLog::record('user.role_changed', "Changed {$user->name}'s role to {$label}", $user);

        return back()->with('status', "{$user->name}'s role is now {$label}.");
    }

    /**
     * Soft-delete an account (FR-F1). The row, its items, claims and audit trail
     * all stay in the database — the account just stops existing for the app and
     * can no longer log in. Reversible via restore().
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403, "You can't delete your own account.");

        $user->delete();

        AuditLog::record('user.deleted', "Deleted account for {$user->name} ({$user->reg_no})", $user);

        return back()->with('status', "{$user->name}'s account has been deleted. Records are kept and the account can be restored.");
    }

    public function restore(User $user): RedirectResponse
    {
        $user->restore();

        AuditLog::record('user.restored', "Restored account for {$user->name} ({$user->reg_no})", $user);

        return back()->with('status', "{$user->name}'s account has been restored.");
    }
}
