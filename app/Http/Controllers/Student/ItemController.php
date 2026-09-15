<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Services\MatchingEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Report an Item (Student/Staff) — FR-B1, FR-B2.
 * One form handles both lost and found via the `type` field.
 * Category/location options come from the admin-managed reference lists (FR-F2).
 */
class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = Item::where('type', 'found')->whereIn('status', ['open', 'matched']);

        if ($request->filled('q')) {
            $search = $request->string('q');
            // Location and category are searched too — they're what the type-ahead offers.
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return view('student.items.browse', [
            'items' => $query->latest()->paginate(9)->withQueryString(),
            'categories' => Category::active()->orderBy('name')->pluck('name'),
        ]);
    }

    /** Everything the signed-in user has reported, searchable and filterable by type. */
    public function mine(Request $request): View
    {
        $query = Item::where('user_id', Auth::id())->withCount('claims');

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if (in_array($request->string('type')->toString(), ['lost', 'found'], true)) {
            $query->where('type', $request->string('type'));
        }

        return view('student.items.mine', [
            'items' => $query->latest()->paginate(9)->withQueryString(),
            'counts' => [
                'all' => Item::where('user_id', Auth::id())->count(),
                'lost' => Item::where('user_id', Auth::id())->where('type', 'lost')->count(),
                'found' => Item::where('user_id', Auth::id())->where('type', 'found')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('student.items.report', [
            'categories' => Category::active()->orderBy('name')->pluck('name'),
            'locations' => Location::active()->orderBy('name')->pluck('name'),
            // Same list again, but with coordinates — lets the map drop a pin
            // automatically when a known location is chosen.
            'mapLocations' => Location::active()->orderBy('name')
                ->get(['name', 'latitude', 'longitude'])
                ->map(fn ($l) => ['name' => $l->name, 'lat' => $l->latitude, 'lng' => $l->longitude])
                ->all(),
        ]);
    }

    public function store(Request $request, MatchingEngine $matcher): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:lost,found'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100', Rule::exists('categories', 'name')->where('is_active', true)],
            'location' => ['required', 'string', 'max:100'],
            // The pin is optional, but a half-submitted pair is a bug, not a choice —
            // require both or neither.
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'], // optional, ≤4 MB
        ], [
            'date.before_or_equal' => 'The date cannot be in the future.',
            'category.exists' => 'Please choose a valid category.',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('items', 'public');
        }

        $item = Item::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status' => 'open',
        ]);

        AuditLog::record('item.reported', "Reported {$item->type} item \"{$item->name}\" at {$item->location}", $item);

        $matches = $matcher->runFor($item);

        $label = $item->isLost() ? 'lost' : 'found';
        $status = "Your {$label} item report has been submitted successfully.";
        $status .= count($matches) > 0
            ? ' We found '.count($matches).' potential '.(count($matches) === 1 ? 'match' : 'matches').' — an officer will review it.'
            : " We'll keep matching it against new reports as they come in.";

        return redirect()
            ->route('student.items.create')
            ->with('status', $status);
    }
}
