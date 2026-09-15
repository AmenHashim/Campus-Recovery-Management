<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Item;
use App\Models\ItemMatch;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Operational analytics (FR-F3) — recovery rate and claim trends over the existing
 * data. Read-only aggregation; nothing here mutates state.
 */
class AnalyticsController extends Controller
{
    public function index(): View
    {
        $totalItems = Item::count();
        $lostCount = Item::where('type', 'lost')->count();
        $foundCount = Item::where('type', 'found')->count();

        // Only FOUND items are ever physically recovered/returned, so recovery rate is
        // measured against the found pool (BR-06-adjacent).
        $foundByStatus = Item::where('type', 'found')
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $returned = (int) ($foundByStatus['returned'] ?? 0);
        $recoveryRate = $foundCount > 0 ? round($returned / $foundCount * 100, 1) : 0.0;

        $claimsByStatus = Claim::selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $verified = (int) ($claimsByStatus['verified'] ?? 0);
        $rejected = (int) ($claimsByStatus['rejected'] ?? 0);
        $pending = (int) ($claimsByStatus['pending'] ?? 0);
        $reviewed = $verified + $rejected;
        $approvalRate = $reviewed > 0 ? round($verified / $reviewed * 100, 1) : 0.0;

        return view('admin.analytics.index', [
            'totalItems' => $totalItems,
            'lostCount' => $lostCount,
            'foundCount' => $foundCount,
            'totalClaims' => Claim::count(),
            'recoveryRate' => $recoveryRate,
            'approvalRate' => $approvalRate,

            'foundByStatus' => $this->orderStatuses($foundByStatus),
            'claimOutcomes' => [
                'Pending' => $pending,
                'Verified' => $verified,
                'Rejected' => $rejected,
            ],

            'byCategory' => Item::selectRaw('category, count(*) as c')
                ->groupBy('category')->orderByDesc('c')->limit(8)
                ->pluck('c', 'category')->all(),

            'byLocation' => Item::selectRaw('location, count(*) as c')
                ->groupBy('location')->orderByDesc('c')->limit(8)
                ->pluck('c', 'location')->all(),

            'trend' => $this->monthlyTrend(),

            'totalMatches' => ItemMatch::count(),
            'avgConfidence' => round((float) ItemMatch::avg('confidence_score'), 1),
            'highConfidence' => ItemMatch::where('confidence_score', '>=', 75)->count(),
        ]);
    }

    /** Keep the lifecycle in its natural order rather than DB/alphabetical order. */
    protected function orderStatuses($byStatus): array
    {
        $ordered = [];
        foreach (['open', 'matched', 'claimed', 'returned', 'closed'] as $status) {
            $ordered[ucfirst($status)] = (int) ($byStatus[$status] ?? 0);
        }

        return $ordered;
    }

    /**
     * Lost vs found reports per month for the last 6 months. Computed in PHP from a
     * light (created_at, type) pull rather than a DB-specific date function, so it
     * behaves the same on MySQL and the sqlite test DB.
     *
     * @return array<string, array{lost:int, found:int}>
     */
    protected function monthlyTrend(): array
    {
        $start = now()->subMonths(5)->startOfMonth();

        // Pre-seed the 6 buckets so empty months still render.
        $buckets = [];
        for ($m = (clone $start); $m <= now(); $m->addMonth()) {
            $buckets[$m->format('Y-m')] = ['lost' => 0, 'found' => 0];
        }

        Item::where('created_at', '>=', $start)
            ->get(['created_at', 'type'])
            ->each(function (Item $item) use (&$buckets) {
                $key = Carbon::parse($item->created_at)->format('Y-m');
                if (isset($buckets[$key])) {
                    $buckets[$key][$item->type]++;
                }
            });

        return $buckets;
    }
}
