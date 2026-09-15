<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Type-ahead suggestions for every search bar in the app (<x-search-bar>).
 *
 * Each method returns at most self::LIMIT rows shaped as
 *   ['label' => …, 'meta' => …, 'icon' => 'ti-…', 'value' => …, 'url' => ?…]
 * where `value` is dropped into the search box and `url` — when present — is a
 * filter shortcut the browser follows directly instead of running the search.
 *
 * Authorization comes from the route group each endpoint is registered in, so a
 * student can never reach the user or audit suggestions.
 */
class SearchSuggestionController extends Controller
{
    private const LIMIT = 8;

    /** Found-item pool — Browse & Search (student/staff). */
    public function browseItems(Request $request): JsonResponse
    {
        if (! $term = $this->term($request)) {
            return response()->json([]);
        }

        $pool = fn () => Item::where('type', 'found')->whereIn('status', ['open', 'matched']);

        $names = $pool()->where('name', 'like', "%{$term}%")
            ->distinct()->orderBy('name')->limit(5)->pluck('name')
            ->map(fn ($name) => [
                'label' => $name,
                'meta' => 'Item',
                'icon' => 'ti-box',
                'value' => $name,
            ]);

        $categories = $pool()->where('category', 'like', "%{$term}%")
            ->distinct()->orderBy('category')->limit(3)->pluck('category')
            ->map(fn ($category) => [
                'label' => $category,
                'meta' => 'Filter by category',
                'icon' => 'ti-category',
                'value' => '',
                'url' => route('student.items.index', ['category' => $category]),
            ]);

        $locations = $pool()->where('location', 'like', "%{$term}%")
            ->distinct()->orderBy('location')->limit(3)->pluck('location')
            ->map(fn ($location) => [
                'label' => $location,
                'meta' => 'Location',
                'icon' => 'ti-map-pin',
                'value' => $location,
            ]);

        return $this->respond($names->concat($categories)->concat($locations));
    }

    /** The signed-in user's own reports — My Reports (student/staff). */
    public function myItems(Request $request): JsonResponse
    {
        if (! $term = $this->term($request)) {
            return response()->json([]);
        }

        $items = Item::where('user_id', Auth::id())
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%"))
            ->latest()->limit(self::LIMIT)->get();

        return $this->respond($items->map(fn (Item $item) => [
            'label' => $item->name,
            'meta' => ucfirst($item->type).' · '.$item->location,
            'icon' => $item->categoryIcon(),
            'value' => $item->name,
        ]));
    }

    /** Claims queue — searchable by token, claimant name or reg. no. (officer). */
    public function claims(Request $request): JsonResponse
    {
        if (! $term = $this->term($request)) {
            return response()->json([]);
        }

        $claims = Claim::with(['item', 'claimant'])
            ->where(fn ($q) => $q->where('token', 'like', "%{$term}%")
                ->orWhereHas('claimant', fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('reg_no', 'like', "%{$term}%")))
            ->orderByRaw("status = 'pending' desc")
            ->latest()->limit(self::LIMIT)->get();

        return $this->respond($claims->map(fn (Claim $claim) => [
            'label' => $claim->token,
            'meta' => $claim->claimant->name.' · '.$claim->item->name,
            'icon' => $claim->status === 'pending' ? 'ti-clock' : 'ti-ticket',
            'value' => $claim->token,
        ]));
    }

    /** Accounts — User Management (admin). Includes deleted accounts. */
    public function users(Request $request): JsonResponse
    {
        if (! $term = $this->term($request)) {
            return response()->json([]);
        }

        $users = User::withTrashed()
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('reg_no', 'like', "%{$term}%"))
            ->orderBy('name')->limit(self::LIMIT)->get();

        return $this->respond($users->map(fn (User $user) => [
            'label' => $user->name,
            'meta' => $user->reg_no.' · '.$user->roleLabel().($user->trashed() ? ' · deleted' : ''),
            'icon' => 'ti-user',
            'value' => $user->reg_no,
        ]));
    }

    /** Audit trail — action names and descriptions (admin). */
    public function auditLogs(Request $request): JsonResponse
    {
        if (! $term = $this->term($request)) {
            return response()->json([]);
        }

        $actions = AuditLog::where('action', 'like', "%{$term}%")
            ->distinct()->orderBy('action')->limit(4)->pluck('action')
            ->map(fn ($action) => [
                'label' => $action,
                'meta' => 'Action',
                'icon' => 'ti-bolt',
                'value' => $action,
            ]);

        $descriptions = AuditLog::where('description', 'like', "%{$term}%")
            ->distinct()->latest()->limit(4)->pluck('description')
            ->map(fn ($description) => [
                'label' => $description,
                'meta' => 'Entry',
                'icon' => 'ti-history',
                'value' => $description,
            ]);

        return $this->respond($actions->concat($descriptions));
    }

    /** Two characters is the point where a prefix stops matching half the table. */
    private function term(Request $request): ?string
    {
        $term = trim($request->string('q')->toString());

        return mb_strlen($term) >= 2 ? $term : null;
    }

    private function respond(\Illuminate\Support\Collection $suggestions): JsonResponse
    {
        return response()->json($suggestions->take(self::LIMIT)->values());
    }
}
