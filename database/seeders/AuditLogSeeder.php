<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Database\Seeder;

/**
 * Backfills the audit trail from already-seeded claims and guest reports, so the log
 * viewer and the dashboard "Audit Entries" count have realistic history to show. Runs
 * after ClaimSeeder. Timestamps mirror the source events rather than seed-run time.
 */
class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        // Officer-filed guest reports.
        Item::with(['guestReporter'])->whereNotNull('guest_reporter_id')->get()
            ->each(function (Item $item) {
                $this->log('item.guest_reported',
                    "Filed guest {$item->type} report \"{$item->name}\" for ".($item->guestReporter?->name ?? 'a guest'),
                    $item, $item->filed_by, $item->created_at);
            });

        // Claim lifecycle: submit → verify/reject.
        Claim::with('item')->get()->each(function (Claim $claim) {
            $this->log('claim.submitted',
                "Submitted claim {$claim->token} for \"{$claim->item?->name}\"",
                $claim, $claim->claimant_user_id, $claim->created_at);

            if ($claim->status === 'verified') {
                $this->log('claim.verified',
                    "Verified claim {$claim->token} for \"{$claim->item?->name}\"",
                    $claim, $claim->verified_by, $claim->verified_at);
            } elseif ($claim->status === 'rejected') {
                $this->log('claim.rejected',
                    "Rejected claim {$claim->token} for \"{$claim->item?->name}\" — {$claim->notes}",
                    $claim, $claim->verified_by, $claim->verified_at);
            }
        });
    }

    protected function log(string $action, string $description, $subject, ?int $actorId, $at): void
    {
        AuditLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'ip_address' => null,
            'created_at' => $at,
        ]);
    }
}
