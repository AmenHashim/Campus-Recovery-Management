<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\GuestReporter;
use App\Models\Item;
use App\Models\Location;
use App\Services\MatchingEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Officer files a lost/found report on behalf of a walk-in guest (BR-01/BR-02) —
 * guests never get a user account, so the report is attached to a GuestReporter record instead.
 * Category/location options come from the admin-managed reference lists (FR-F2).
 */
class ItemController extends Controller
{
    public function create(): View
    {
        return view('officer.items.report', [
            'categories' => Category::active()->orderBy('name')->pluck('name'),
            'locations' => Location::active()->orderBy('name')->pluck('name'),
            'mapLocations' => Location::active()->orderBy('name')
                ->get(['name', 'latitude', 'longitude'])
                ->map(fn ($l) => ['name' => $l->name, 'lat' => $l->latitude, 'lng' => $l->longitude])
                ->all(),
        ]);
    }

    public function store(Request $request, MatchingEngine $matcher): RedirectResponse
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_id_type' => ['nullable', 'string', 'max:100'],
            'guest_id_number' => ['nullable', 'string', 'max:100'],

            'type' => ['required', 'in:lost,found'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100', Rule::exists('categories', 'name')->where('is_active', true)],
            'location' => ['required', 'string', 'max:100'],
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

        $guest = GuestReporter::create([
            'name' => $validated['guest_name'],
            'phone' => $validated['guest_phone'] ?? null,
            'email' => $validated['guest_email'] ?? null,
            'id_type' => $validated['guest_id_type'] ?? null,
            'id_number' => $validated['guest_id_number'] ?? null,
        ]);

        $item = Item::create([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'location' => $validated['location'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
            'contact' => $validated['contact'] ?? null,
            'image' => $validated['image'] ?? null,
            'guest_reporter_id' => $guest->id,
            'filed_by' => Auth::id(),
            'status' => 'open',
        ]);

        AuditLog::record('item.guest_reported', "Filed guest {$item->type} report \"{$item->name}\" for {$guest->name}", $item);

        $matches = $matcher->runFor($item);

        $label = $item->isLost() ? 'lost' : 'found';
        $status = "Guest {$label} report for \"{$item->name}\" filed successfully.";
        $status .= count($matches) > 0
            ? ' We found '.count($matches).' potential '.(count($matches) === 1 ? 'match' : 'matches').'.'
            : '';

        return redirect()
            ->route('officer.guest-reports.create')
            ->with('status', $status);
    }

    /**
     * Storage status board (BR-05-ish) — only found items are ever physically held by the
     * office, so lost reports don't appear here.
     */
    public function intake(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'in_storage';

        $query = Item::with(['reporter', 'guestReporter'])->where('type', 'found');

        $status === 'in_storage'
            ? $query->whereIn('status', ['open', 'matched'])
            : $query->where('status', $status);

        return view('officer.items.intake', [
            'items' => $query->latest()->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function markReturned(Item $item): RedirectResponse
    {
        abort_if($item->type !== 'found', 404);
        abort_unless(in_array($item->status, ['open', 'matched'], true), 422,
            'This item already has a claim in progress or has been resolved — use the Claims page instead.');

        $item->update(['status' => 'returned']);

        AuditLog::record('item.returned', "Marked \"{$item->name}\" as returned to its owner", $item);

        return back()->with('status', "\"{$item->name}\" marked as returned to its owner.");
    }

    /**
     * Closing is the negative outcome, so the officer states why. The item table has
     * no notes column — the reason lives in the audit trail, which is where a
     * "why was this item closed?" question gets answered later anyway (FR-F4).
     */
    public function close(Request $request, Item $item): RedirectResponse
    {
        abort_if($item->type !== 'found', 404);
        abort_unless(in_array($item->status, ['open', 'matched'], true), 422,
            'This item already has a claim in progress or has been resolved — use the Claims page instead.');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Please give a reason for closing this item.',
        ]);

        $item->update(['status' => 'closed']);

        AuditLog::record('item.closed', "Closed item \"{$item->name}\" — {$validated['reason']}", $item);

        return back()->with('status', "\"{$item->name}\" closed.");
    }
}
