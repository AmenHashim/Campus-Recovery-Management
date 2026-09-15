<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Audit-log viewer (FR-F4) — read-only. Entries are immutable; this only lists them,
 * filtered by action group and free-text search over the description.
 */
class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('group') && array_key_exists($request->string('group')->toString(), AuditLog::GROUPS)) {
            $query->inGroup($request->string('group')->toString());
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        return view('admin.audit.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'groups' => array_keys(AuditLog::GROUPS),
        ]);
    }
}
