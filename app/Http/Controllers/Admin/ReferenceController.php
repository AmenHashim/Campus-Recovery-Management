<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reference-data management (FR-F2) — the category and location lists that populate the
 * item-report forms. These drive the dropdowns; items store the chosen value as a string,
 * so an in-use entry is deactivated rather than deleted (deletion is blocked while in use).
 */
class ReferenceController extends Controller
{
    public function index(): View
    {
        return view('admin.reference.index', [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /* ─────────────── Categories ─────────────── */

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?: null,
            'is_active' => true,
        ]);

        AuditLog::record('category.created', "Added category \"{$category->name}\"", $category);

        return back()->with('status', "Category \"{$validated['name']}\" added.");
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $original = $category->name;
        $category->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?: null,
        ]);

        AuditLog::record('category.updated', "Updated category \"{$original}\" → \"{$category->name}\"", $category);

        return back()->with('status', 'Category updated.');
    }

    public function toggleCategory(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        $state = $category->is_active ? 'active' : 'hidden';
        AuditLog::record('category.toggled', "Set category \"{$category->name}\" to {$state}", $category);

        return back()->with('status', "\"{$category->name}\" is now {$state}.");
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if (($count = $category->itemCount()) > 0) {
            return back()->with('error', "\"{$category->name}\" is used by {$count} item(s) — deactivate it instead of deleting.");
        }

        $name = $category->name;
        $category->delete();

        AuditLog::record('category.deleted', "Deleted category \"{$name}\"");

        return back()->with('status', 'Category deleted.');
    }

    /* ─────────────── Locations ─────────────── */

    public function storeLocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('locations', 'name')],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);

        $location = Location::create([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_active' => true,
        ]);

        AuditLog::record('location.created', "Added location \"{$location->name}\"", $location);

        return back()->with('status', "Location \"{$validated['name']}\" added.");
    }

    public function updateLocation(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('locations', 'name')->ignore($location)],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ]);

        $original = $location->name;
        $location->update([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        AuditLog::record('location.updated', "Updated location \"{$original}\" → \"{$location->name}\"", $location);

        return back()->with('status', 'Location updated.');
    }

    public function toggleLocation(Location $location): RedirectResponse
    {
        $location->update(['is_active' => ! $location->is_active]);

        $state = $location->is_active ? 'active' : 'hidden';
        AuditLog::record('location.toggled', "Set location \"{$location->name}\" to {$state}", $location);

        return back()->with('status', "\"{$location->name}\" is now {$state}.");
    }

    public function destroyLocation(Location $location): RedirectResponse
    {
        if (($count = $location->itemCount()) > 0) {
            return back()->with('error', "\"{$location->name}\" is used by {$count} item(s) — deactivate it instead of deleting.");
        }

        $name = $location->name;
        $location->delete();

        AuditLog::record('location.deleted', "Deleted location \"{$name}\"");

        return back()->with('status', 'Location deleted.');
    }
}
