<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The student/staff view is personal, not operational: what have I reported,
 * where are my claims, and is there anything new worth checking.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $mine = fn () => Item::where('user_id', $user->id);
        $myClaims = fn () => Claim::where('claimant_user_id', $user->id);

        $claimsByStatus = $myClaims()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('student.dashboard', [
            'itemsReported' => $mine()->count(),
            'activeClaims' => (clone $myClaims())->whereIn('status', ['pending', 'verified'])->count(),
            'pendingClaims' => (int) ($claimsByStatus['pending'] ?? 0),
            'unreadNotifications' => $user->appNotifications()->unread()->count(),

            // Recovered = my found reports that made it back to an owner, plus my verified claims.
            'itemsRecovered' => $mine()->where('type', 'found')->where('status', 'returned')->count()
                + (int) ($claimsByStatus['verified'] ?? 0),

            'myReportsByStatus' => $this->myReportsByStatus($user->id),
            'myClaimOutcomes' => [
                'Pending' => (int) ($claimsByStatus['pending'] ?? 0),
                'Verified' => (int) ($claimsByStatus['verified'] ?? 0),
                'Rejected' => (int) ($claimsByStatus['rejected'] ?? 0),
            ],
            'totalMyClaims' => $myClaims()->count(),

            'recentItems' => $mine()->latest()->limit(5)->get(),
            'recentClaims' => Claim::with('item')->where('claimant_user_id', $user->id)
                ->latest()->limit(5)->get(),

            // The found pool, so there's something to act on even for a brand-new account.
            'latestFound' => Item::where('type', 'found')
                ->whereIn('status', ['open', 'matched'])
                // Your own reports aren't claimable by you, so they'd be noise here.
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id))
                ->latest()->limit(5)->get(),

            'newFoundItems' => Item::where('type', 'found')
                ->whereIn('status', ['open', 'matched'])
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ]);
    }

    /** @return array<string, int> */
    private function myReportsByStatus(int $userId): array
    {
        $counts = Item::where('user_id', $userId)
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $ordered = [];
        foreach (['open', 'matched', 'claimed', 'returned', 'closed'] as $status) {
            $count = (int) ($counts[$status] ?? 0);
            if ($count > 0) {
                $ordered[ucfirst($status)] = $count;
            }
        }

        return $ordered;
    }
}
