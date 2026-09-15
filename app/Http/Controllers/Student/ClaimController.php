<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use App\Services\TokenGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Student/Staff claim submission — FR-B4-ish "I think this is mine".
 * Verification by an Officer (checking a physical ID against the token) is a later sprint.
 */
class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        $mine = fn () => Claim::where('claimant_user_id', Auth::id());

        $query = $mine()->with(['item', 'verifier'])->latest();

        if (in_array($request->string('status')->toString(), ['pending', 'verified', 'rejected'], true)) {
            $query->where('status', $request->string('status'));
        }

        return view('student.claims.index', [
            'claims' => $query->get(),
            'counts' => [
                'all' => $mine()->count(),
                'pending' => $mine()->where('status', 'pending')->count(),
                'verified' => $mine()->where('status', 'verified')->count(),
                'rejected' => $mine()->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function store(Item $item): RedirectResponse
    {
        abort_if($item->type !== 'found', 404);

        if ($item->user_id === Auth::id()) {
            return back()->with('error', "You can't claim your own report.");
        }

        if (! in_array($item->status, ['open', 'matched'], true)) {
            return back()->with('error', 'This item is no longer available to claim.');
        }

        $alreadyClaimed = Claim::where('item_id', $item->id)
            ->where('claimant_user_id', Auth::id())
            ->where('status', 'pending')
            ->exists();

        if ($alreadyClaimed) {
            return back()->with('error', 'You already have a pending claim on this item.');
        }

        $claim = Claim::create([
            'item_id' => $item->id,
            'claimant_user_id' => Auth::id(),
            'token' => TokenGenerator::claimToken(),
            'status' => 'pending',
        ]);

        $item->update(['status' => 'claimed']);

        if ($item->user_id) {
            AppNotification::create([
                'user_id' => $item->user_id,
                'type' => 'claim_submitted',
                'title' => 'Someone Claimed Your Found Item',
                'message' => Auth::user()->name." submitted a claim for \"{$item->name}\". An officer will verify it in person.",
            ]);
        }

        foreach (User::role(User::ROLE_OFFICER)->get() as $officer) {
            AppNotification::create([
                'user_id' => $officer->id,
                'type' => 'claim_submitted',
                'title' => 'New Claim Awaiting Verification',
                'message' => Auth::user()->name." submitted a claim for \"{$item->name}\" (token {$claim->token}).",
            ]);
        }

        AuditLog::record('claim.submitted', "Submitted claim {$claim->token} for \"{$item->name}\"", $claim);

        return redirect()
            ->route('student.claims.index')
            ->with('status', 'Claim submitted successfully.')
            ->with('new_claim_token', $claim->token)
            ->with('new_claim_item', $item->name);
    }
}
