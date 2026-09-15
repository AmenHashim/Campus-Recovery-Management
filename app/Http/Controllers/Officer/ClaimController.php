<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Claim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Officer claim verification — BR-03. Verifying checks the physical item/ID in person;
 * this just records the outcome, rewards the finder, and notifies the claimant.
 */
class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        $query = Claim::with(['item', 'claimant'])
            ->orderByRaw("status = 'pending' desc")
            ->latest();

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('token', 'like', "%{$search}%")
                    ->orWhereHas('claimant', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('reg_no', 'like', "%{$search}%");
                    });
            });
        }

        if (in_array($request->string('status')->toString(), ['pending', 'verified', 'rejected'], true)) {
            $query->where('status', $request->string('status'));
        }

        return view('officer.claims.index', [
            'claims' => $query->paginate(15)->withQueryString(),
            'counts' => [
                'all' => Claim::count(),
                'pending' => Claim::where('status', 'pending')->count(),
                'verified' => Claim::where('status', 'verified')->count(),
                'rejected' => Claim::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function verify(Claim $claim): RedirectResponse
    {
        abort_if($claim->status !== 'pending', 422, 'This claim has already been reviewed.');

        $claim->update([
            'status' => 'verified',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $claim->item->update(['status' => 'returned']);

        // Reward the finder (registered users only — guests aren't tracked for reputation).
        if ($claim->item->isFound() && $claim->item->reporter) {
            $claim->item->reporter->increment('reputation_points', 10);
        }

        AppNotification::create([
            'user_id' => $claim->claimant_user_id,
            'type' => 'claim_verified',
            'title' => 'Claim Verified',
            'message' => "Your claim for \"{$claim->item->name}\" has been verified. Please collect it from the Lost & Found office.",
        ]);

        AuditLog::record('claim.verified', "Verified claim {$claim->token} for \"{$claim->item->name}\"", $claim);

        return back()->with('status', 'Claim verified and item marked as returned.');
    }

    public function reject(Request $request, Claim $claim): RedirectResponse
    {
        abort_if($claim->status !== 'pending', 422, 'This claim has already been reviewed.');

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $claim->update([
            'status' => 'rejected',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'notes' => $validated['notes'],
        ]);

        if ($claim->item->status === 'claimed') {
            $claim->item->update(['status' => 'open']);
        }

        AppNotification::create([
            'user_id' => $claim->claimant_user_id,
            'type' => 'claim_rejected',
            'title' => 'Claim Rejected',
            'message' => "Your claim for \"{$claim->item->name}\" was rejected. Reason: {$validated['notes']}",
        ]);

        AuditLog::record('claim.rejected', "Rejected claim {$claim->token} for \"{$claim->item->name}\" — {$validated['notes']}", $claim);

        return back()->with('status', 'Claim rejected.');
    }
}
