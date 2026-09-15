<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The officer's daily worklist: what's waiting on them, what's aging in storage,
 * and how the week is going. Everything here is read-only aggregation.
 */
class DashboardController extends Controller
{
    /** An unreviewed claim older than this is flagged on the board. */
    private const CLAIM_SLA_DAYS = 3;

    /** Found items held longer than this are due a retention review. */
    private const STORAGE_AGING_DAYS = 30;

    public function index(): View
    {
        $inStorage = Item::where('type', 'found')->whereIn('status', ['open', 'matched']);

        $pending = Claim::where('status', 'pending');
        $overdueClaims = (clone $pending)->where('created_at', '<', now()->subDays(self::CLAIM_SLA_DAYS))->count();

        $returnedThisMonth = Item::where('type', 'found')
            ->where('status', 'returned')
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();

        return view('officer.dashboard', [
            'pendingClaims' => (clone $pending)->count(),
            'overdueClaims' => $overdueClaims,
            'slaDays' => self::CLAIM_SLA_DAYS,
            'itemsInStorage' => (clone $inStorage)->count(),
            'agingItems' => (clone $inStorage)->where('date', '<', now()->subDays(self::STORAGE_AGING_DAYS))->count(),
            'agingDays' => self::STORAGE_AGING_DAYS,
            'returnedThisMonth' => $returnedThisMonth,
            'guestReportsFiled' => Item::whereNotNull('guest_reporter_id')->count(),
            'unreadNotifications' => AppNotification::where('user_id', Auth::id())->unread()->count(),

            // Worklist — oldest first, because those are the ones people are waiting on.
            'claimQueue' => Claim::with(['item', 'claimant'])
                ->where('status', 'pending')
                ->oldest()
                ->limit(6)
                ->get(),

            // Longest-held items: the retention-review shortlist.
            'oldestInStorage' => (clone $inStorage)
                ->orderBy('date')
                ->limit(6)
                ->get(),

            'storageByCategory' => (clone $inStorage)
                ->selectRaw('category, count(*) as c')
                ->groupBy('category')->orderByDesc('c')->limit(6)
                ->pluck('c', 'category')->all(),

            'weeklyIntake' => $this->weeklyIntake(),
        ]);
    }

    /**
     * Reports filed per day over the last 7 days, split lost/found. Bucketed in PHP so
     * the same code runs on MySQL and the sqlite test database.
     *
     * @return array<string, array{found:int, lost:int}>
     */
    private function weeklyIntake(): array
    {
        $start = now()->subDays(6)->startOfDay();

        $days = [];
        for ($day = (clone $start); $day <= now(); $day->addDay()) {
            $days[$day->format('D')] = ['found' => 0, 'lost' => 0];
        }

        Item::where('created_at', '>=', $start)
            ->get(['created_at', 'type'])
            ->each(function (Item $item) use (&$days) {
                $key = Carbon::parse($item->created_at)->format('D');
                if (isset($days[$key])) {
                    $days[$key][$item->type]++;
                }
            });

        return $days;
    }
}
