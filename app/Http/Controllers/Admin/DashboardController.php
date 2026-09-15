<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * System overview. Deliberately a level above Analytics (FR-F3): health of the
 * system and who's using it, with the deep recovery/claim breakdowns one click away.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $foundCount = Item::where('type', 'found')->count();
        $returned = Item::where('type', 'found')->where('status', 'returned')->count();

        $claimsByStatus = Claim::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $verified = (int) ($claimsByStatus['verified'] ?? 0);
        $rejected = (int) ($claimsByStatus['rejected'] ?? 0);
        $reviewed = $verified + $rejected;

        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'newUsersThisMonth' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'suspendedUsers' => User::where('status', User::STATUS_SUSPENDED)->count(),
            'deletedUsers' => User::onlyTrashed()->count(),

            'totalItems' => Item::count(),
            'openClaims' => (int) ($claimsByStatus['pending'] ?? 0),
            'auditEntries' => AuditLog::count(),

            'recoveryRate' => $foundCount > 0 ? round($returned / $foundCount * 100, 1) : 0.0,
            'approvalRate' => $reviewed > 0 ? round($verified / $reviewed * 100, 1) : 0.0,

            'usersByRole' => $this->usersByRole(),
            'itemsByStatus' => $this->itemsByStatus(),
            'claimOutcomes' => [
                'Pending' => (int) ($claimsByStatus['pending'] ?? 0),
                'Verified' => $verified,
                'Rejected' => $rejected,
            ],

            'recentActivity' => AuditLog::with('user')->latest()->limit(7)->get(),
            'newestUsers' => User::with('roles')->latest()->limit(5)->get(),
            'trend' => $this->monthlyTrend(),
        ]);
    }

    /** @return array<string, int> */
    private function usersByRole(): array
    {
        return [
            'Students' => User::role(User::ROLE_STUDENT_STAFF)->where('user_type', User::TYPE_STUDENT)->count(),
            'Staff' => User::role(User::ROLE_STUDENT_STAFF)->where('user_type', User::TYPE_STAFF)->count(),
            'Officers' => User::role(User::ROLE_OFFICER)->count(),
            'Admins' => User::role(User::ROLE_ADMIN)->count(),
        ];
    }

    /** @return array<string, int> */
    private function itemsByStatus(): array
    {
        $counts = Item::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $ordered = [];
        foreach (['open', 'matched', 'claimed', 'returned', 'closed'] as $status) {
            $ordered[ucfirst($status)] = (int) ($counts[$status] ?? 0);
        }

        return $ordered;
    }

    /**
     * Lost vs found per month for the last 6 months. Bucketed in PHP so it behaves
     * identically on MySQL and the sqlite test database.
     *
     * @return array<string, array{found:int, lost:int}>
     */
    private function monthlyTrend(): array
    {
        $start = now()->subMonths(5)->startOfMonth();

        $buckets = [];
        for ($month = (clone $start); $month <= now(); $month->addMonth()) {
            $buckets[$month->format('M')] = ['found' => 0, 'lost' => 0];
        }

        Item::where('created_at', '>=', $start)
            ->get(['created_at', 'type'])
            ->each(function (Item $item) use (&$buckets) {
                $key = Carbon::parse($item->created_at)->format('M');
                if (isset($buckets[$key])) {
                    $buckets[$key][$item->type]++;
                }
            });

        return $buckets;
    }
}
