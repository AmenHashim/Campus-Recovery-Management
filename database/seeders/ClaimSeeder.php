<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use App\Services\TokenGenerator;
use Database\Factories\ItemFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Claims are derived from real found items and pushed through the same state
 * transitions as Student\ClaimController / Officer\ClaimController (submit → notify →
 * verify/reject), so the seeded data — and the notifications it produces — stays
 * consistent with what the app itself would generate.
 */
class ClaimSeeder extends Seeder
{
    protected const TOTAL_CLAIMS = 300;

    protected const REJECTION_REASONS = [
        'ID did not match the claimant\'s details.',
        'Could not describe the item\'s distinguishing marks correctly.',
        'Item already returned to another claimant.',
    ];

    public function run(): void
    {
        $claimantIds = User::role(User::ROLE_STUDENT_STAFF)->pluck('id')->all();
        $officerIds = User::role(User::ROLE_OFFICER)->pluck('id')->all();

        $items = Item::where('type', 'found')
            ->whereIn('status', ['open', 'matched'])
            ->inRandomOrder()
            ->limit(self::TOTAL_CLAIMS)
            ->get();

        foreach ($items as $item) {
            $claimant = Arr::random(array_values(array_diff($claimantIds, [$item->user_id])));

            $claim = Claim::create([
                'item_id' => $item->id,
                'claimant_user_id' => $claimant,
                'token' => TokenGenerator::claimToken(),
                'status' => 'pending',
            ]);

            $item->update(['status' => 'claimed']);

            if ($item->user_id) {
                AppNotification::create([
                    'user_id' => $item->user_id,
                    'type' => 'claim_submitted',
                    'title' => 'Someone Claimed Your Found Item',
                    'message' => "A claim was submitted for \"{$item->name}\". An officer will verify it in person.",
                ]);
            }

            foreach ($officerIds as $officerId) {
                AppNotification::create([
                    'user_id' => $officerId,
                    'type' => 'claim_submitted',
                    'title' => 'New Claim Awaiting Verification',
                    'message' => "A claim was submitted for \"{$item->name}\" (token {$claim->token}).",
                ]);
            }

            $this->resolveClaim($claim, $item, $claimant, $officerIds);
        }
    }

    protected function resolveClaim(Claim $claim, Item $item, int $claimant, array $officerIds): void
    {
        $resolution = ItemFactory::weighted(['pending' => 40, 'verified' => 35, 'rejected' => 25]);

        if ($resolution === 'verified') {
            $claim->update([
                'status' => 'verified',
                'verified_by' => Arr::random($officerIds),
                'verified_at' => now(),
            ]);
            $item->update(['status' => 'returned']);

            if ($item->reporter) {
                $item->reporter->increment('reputation_points', 10);
            }

            AppNotification::create([
                'user_id' => $claimant,
                'type' => 'claim_verified',
                'title' => 'Claim Verified',
                'message' => "Your claim for \"{$item->name}\" has been verified. Please collect it from the Lost & Found office.",
            ]);
        } elseif ($resolution === 'rejected') {
            $notes = fake()->randomElement(self::REJECTION_REASONS);

            $claim->update([
                'status' => 'rejected',
                'verified_by' => Arr::random($officerIds),
                'verified_at' => now(),
                'notes' => $notes,
            ]);
            $item->update(['status' => 'open']);

            AppNotification::create([
                'user_id' => $claimant,
                'type' => 'claim_rejected',
                'title' => 'Claim Rejected',
                'message' => "Your claim for \"{$item->name}\" was rejected. Reason: {$notes}",
            ]);
        }
    }
}
