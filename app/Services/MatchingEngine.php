<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Item;
use App\Models\ItemMatch;
use Carbon\Carbon;

/**
 * Weighted matching engine — v1 (no AI/image matching yet).
 *
 * Weights: category 20%, location 20%, date proximity 15%,
 * text similarity (name+description) 30%, shared attribute keywords 15%.
 */
class MatchingEngine
{
    public const WEIGHT_CATEGORY = 0.20;
    public const WEIGHT_LOCATION = 0.20;
    public const WEIGHT_DATE = 0.15;
    public const WEIGHT_TEXT = 0.30;
    public const WEIGHT_ATTRIBUTE = 0.15;

    /**
     * FR-C3 thresholds:
     *   score >= 75  → "Likely Match": persisted AND both parties notified
     *   40 <= score  → "Possible Match": persisted, shown passively, NO notification
     *   score < 40   → not persisted at all
     */
    public const THRESHOLD_LIKELY = 75.0;
    public const THRESHOLD_POSSIBLE = 40.0;

    /**
     * Run matching for a newly created item against opposite-type items.
     * Called synchronously from ItemController::store().
     *
     * @return ItemMatch[]
     */
    public function runFor(Item $item): array
    {
        $oppositeType = $item->isLost() ? 'found' : 'lost';

        $candidates = Item::where('type', $oppositeType)
            ->whereIn('status', ['open', 'matched'])
            ->get();

        $created = [];

        foreach ($candidates as $candidate) {
            $score = $this->score($item, $candidate);

            if ($score < self::THRESHOLD_POSSIBLE) {
                continue; // not worth persisting
            }

            [$lost, $found] = $item->isLost() ? [$item, $candidate] : [$candidate, $item];

            $match = ItemMatch::firstOrCreate(
                ['lost_item_id' => $lost->id, 'found_item_id' => $found->id],
                ['confidence_score' => $score, 'match_status' => 'pending']
            );

            $previousScore = $match->wasRecentlyCreated ? null : (float) $match->confidence_score;

            if (! $match->wasRecentlyCreated) {
                $match->update(['confidence_score' => $score]);
            }

            // FR-C3: only a Likely match (>=75) notifies. A Possible match (40-74) is
            // persisted and surfaces passively in the UI. On re-scoring, notify only when
            // the pair crosses into Likely for the first time, so repeated runs can't spam.
            $crossedIntoLikely = $score >= self::THRESHOLD_LIKELY
                && ($previousScore === null || $previousScore < self::THRESHOLD_LIKELY);

            if ($crossedIntoLikely) {
                $this->notifyMatch($lost, $score);
                $this->notifyMatch($found, $score);
            }

            if ($lost->status === 'open') {
                $lost->update(['status' => 'matched']);
            }
            if ($found->status === 'open') {
                $found->update(['status' => 'matched']);
            }

            $created[] = $match;
        }

        return $created;
    }

    public function score(Item $a, Item $b): float
    {
        $categoryScore = strcasecmp($a->category, $b->category) === 0 ? 100 : 0;
        $locationScore = $this->locationScore($a->location, $b->location);
        $dateScore = $this->dateScore($a->date, $b->date);
        $textScore = $this->textSimilarityScore(
            $a->name.' '.$a->description,
            $b->name.' '.$b->description
        );
        $attributeScore = $this->attributeScore($a->description, $b->description);

        $final = ($categoryScore * self::WEIGHT_CATEGORY)
            + ($locationScore * self::WEIGHT_LOCATION)
            + ($dateScore * self::WEIGHT_DATE)
            + ($textScore * self::WEIGHT_TEXT)
            + ($attributeScore * self::WEIGHT_ATTRIBUTE);

        return round($final, 2);
    }

    protected function locationScore(?string $a, ?string $b): float
    {
        if (! $a || ! $b) {
            return 0;
        }

        $a = strtolower(trim($a));
        $b = strtolower(trim($b));

        if ($a === $b) {
            return 100;
        }

        // Free-text location field — give partial credit for a substring match
        // (e.g. "Library" vs "Main Library") rather than a hard zero.
        return str_contains($a, $b) || str_contains($b, $a) ? 60 : 0;
    }

    protected function dateScore($dateA, $dateB): float
    {
        if (! $dateA || ! $dateB) {
            return 0;
        }

        $diffDays = abs(Carbon::parse($dateA)->diffInDays(Carbon::parse($dateB)));

        return match (true) {
            $diffDays === 0 => 100,
            $diffDays <= 1 => 90,
            $diffDays <= 3 => 70,
            $diffDays <= 7 => 40,
            $diffDays <= 14 => 15,
            default => 0,
        };
    }

    protected function textSimilarityScore(string $a, string $b): float
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));

        if ($a === '' || $b === '') {
            return 0;
        }

        similar_text($a, $b, $percent);

        return round($percent, 2);
    }

    protected function attributeScore(?string $descA, ?string $descB): float
    {
        // v1 heuristic: look for shared "distinguishing" keywords (colors/brands) in free text.
        $keywords = [
            'black', 'white', 'blue', 'red', 'grey', 'gray', 'green', 'brown',
            'samsung', 'apple', 'iphone', 'hp', 'dell', 'lenovo', 'nike', 'adidas',
        ];

        $descA = strtolower($descA ?? '');
        $descB = strtolower($descB ?? '');

        $matches = 0;
        $checked = 0;

        foreach ($keywords as $kw) {
            $inA = str_contains($descA, $kw);
            $inB = str_contains($descB, $kw);
            if ($inA || $inB) {
                $checked++;
                if ($inA && $inB) {
                    $matches++;
                }
            }
        }

        if ($checked === 0) {
            return 50; // neutral — no distinguishing attributes mentioned either side
        }

        return round(($matches / $checked) * 100, 2);
    }

    /** Only ever called for a Likely match (>=75) — see runFor(). */
    protected function notifyMatch(Item $item, float $score): void
    {
        if (! $item->user_id) {
            return; // guest reporters have no login to notify (BR-02)
        }

        AppNotification::create([
            'user_id' => $item->user_id,
            'type' => 'match_found',
            'title' => 'Likely Match Found',
            'message' => "We found a likely match ({$score}% confidence) for your {$item->type} report \"{$item->name}\". Visit the Lost & Found office to follow up.",
        ]);
    }
}
