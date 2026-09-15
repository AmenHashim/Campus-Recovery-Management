<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemMatch;
use App\Models\User;
use App\Services\MatchingEngine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-C1–C3 — the weighted scoring model and, critically, the notification
 * thresholds: only a Likely match (>=75) notifies; a Possible match (40-74)
 * is persisted but silent; below 40 is not persisted at all.
 */
class MatchingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected MatchingEngine $engine;

    protected User $owner;

    protected User $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Category::create(['name' => 'Electronics', 'icon' => 'ti-device-laptop', 'is_active' => true]);
        Category::create(['name' => 'Documents', 'icon' => 'ti-file', 'is_active' => true]);

        $this->engine = new MatchingEngine;
        $this->owner = User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
        $this->finder = User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
    }

    protected function item(string $type, array $attributes = []): Item
    {
        return Item::create(array_merge([
            'user_id' => $type === 'lost' ? $this->owner->id : $this->finder->id,
            'type' => $type,
            'name' => 'Black Dell Laptop',
            'category' => 'Electronics',
            'location' => 'Main Library',
            'date' => now()->subDays(2)->toDateString(),
            'description' => 'A black Dell laptop in a grey sleeve',
            'status' => 'open',
        ], $attributes));
    }

    /** An identical pair on every signal must land in Likely territory. */
    public function test_identical_reports_score_at_or_above_the_likely_threshold(): void
    {
        $lost = $this->item('lost');
        $found = $this->item('found');

        $score = $this->engine->score($lost, $found);

        $this->assertGreaterThanOrEqual(MatchingEngine::THRESHOLD_LIKELY, $score);
    }

    public function test_a_likely_match_notifies_both_parties(): void
    {
        $this->item('lost');
        $found = $this->item('found');

        $matches = $this->engine->runFor($found);

        $this->assertCount(1, $matches);
        $this->assertGreaterThanOrEqual(MatchingEngine::THRESHOLD_LIKELY, $matches[0]->confidence_score);

        foreach ([$this->owner, $this->finder] as $user) {
            $this->assertDatabaseHas('app_notifications', [
                'user_id' => $user->id,
                'type' => 'match_found',
                'title' => 'Likely Match Found',
            ]);
        }
    }

    /**
     * The regression this test exists for: a Possible match (40-74) must be persisted
     * and shown passively, but must NOT notify anyone (FR-C3).
     */
    public function test_a_possible_match_is_persisted_but_notifies_nobody(): void
    {
        $this->item('lost', [
            'name' => 'Blue Casio Calculator',
            'description' => 'Blue scientific calculator',
        ]);

        // Same category, same location; different item and wording → mid-band score.
        $found = $this->item('found', [
            'name' => 'Black Dell Laptop charger unit',
            'description' => 'Charger brick left on a desk',
            'date' => now()->subDays(6)->toDateString(),
        ]);

        $matches = $this->engine->runFor($found);

        $this->assertCount(1, $matches, 'A mid-band pair should still be persisted.');

        $score = (float) $matches[0]->confidence_score;
        $this->assertGreaterThanOrEqual(MatchingEngine::THRESHOLD_POSSIBLE, $score);
        $this->assertLessThan(MatchingEngine::THRESHOLD_LIKELY, $score);

        $this->assertSame(0, AppNotification::where('type', 'match_found')->count(),
            'A Possible match (40-74) must not notify — FR-C3.');
    }

    public function test_scores_below_the_possible_threshold_are_not_persisted(): void
    {
        $this->item('lost', [
            'name' => 'Student ID Card',
            'category' => 'Documents',
            'location' => 'Cafeteria',
            'description' => 'Laminated identity card',
            'date' => now()->subDays(90)->toDateString(),
        ]);

        $found = $this->item('found', [
            'name' => 'Black Dell Laptop',
            'category' => 'Electronics',
            'location' => 'ICT Lab',
            'description' => 'Dell laptop with a blue sticker',
            'date' => now()->toDateString(),
        ]);

        $matches = $this->engine->runFor($found);

        $this->assertSame([], $matches);
        $this->assertSame(0, ItemMatch::count());
        $this->assertSame(0, AppNotification::where('type', 'match_found')->count());
    }

    /** Re-running the engine must not re-notify on a pair already reported as Likely. */
    public function test_rescoring_an_existing_likely_match_does_not_notify_twice(): void
    {
        $this->item('lost');
        $found = $this->item('found');

        $this->engine->runFor($found);
        $firstRun = AppNotification::where('type', 'match_found')->count();

        $this->engine->runFor($found);

        $this->assertSame($firstRun, AppNotification::where('type', 'match_found')->count());
        $this->assertSame(1, ItemMatch::count(), 'The pair is unique — re-running must not duplicate it.');
    }

    public function test_a_persisted_match_moves_both_items_to_matched(): void
    {
        $lost = $this->item('lost');
        $found = $this->item('found');

        $this->engine->runFor($found);

        $this->assertSame('matched', $lost->fresh()->status);
        $this->assertSame('matched', $found->fresh()->status);
    }

    /** Guests have no login, so a match on a guest-filed report notifies only the account holder (BR-02). */
    public function test_guest_filed_reports_are_not_notified(): void
    {
        $this->item('lost', ['user_id' => null]);
        $found = $this->item('found');

        $this->engine->runFor($found);

        $this->assertSame(1, AppNotification::where('type', 'match_found')->count());
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->finder->id]);
    }

    public function test_date_proximity_decays_as_specified(): void
    {
        $lost = $this->item('lost', ['date' => now()->toDateString()]);

        $sameDay = $this->engine->score($lost, $this->item('found', ['date' => now()->toDateString()]));
        $oneWeek = $this->engine->score($lost, $this->item('found', ['date' => now()->subDays(7)->toDateString()]));
        $oneMonth = $this->engine->score($lost, $this->item('found', ['date' => now()->subDays(30)->toDateString()]));

        $this->assertGreaterThan($oneWeek, $sameDay);
        $this->assertGreaterThan($oneMonth, $oneWeek);
    }
}
