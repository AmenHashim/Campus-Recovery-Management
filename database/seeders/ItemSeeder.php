<?php

namespace Database\Seeders;

use App\Models\GuestReporter;
use App\Models\Item;
use App\Models\User;
use App\Services\MatchingEngine;
use Database\Factories\ItemFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Bulk "noise" items for pagination/search/perf testing, plus a small batch of
 * deliberately similar lost/found pairs pushed through the real MatchingEngine —
 * so confidence scoring has realistic signal (and noise) to work against.
 *
 * Noise items are raw-inserted (bypassing Eloquent events) since there are thousands
 * of them and none of them need to trigger matching; the crafted pairs go through
 * Item::create() + the matcher exactly like Student\ItemController::store does.
 */
class ItemSeeder extends Seeder
{
    protected const TOTAL_NOISE_ITEMS = 2000;

    protected const MATCH_PAIRS = 50;

    protected const GUEST_POOL_SIZE = 60;

    protected const CHUNK_SIZE = 50;

    public function run(): void
    {
        $userIds = User::role(User::ROLE_STUDENT_STAFF)->pluck('id')->all();
        $officerIds = User::role(User::ROLE_OFFICER)->pluck('id')->all();
        $guestIds = GuestReporter::factory()->count(self::GUEST_POOL_SIZE)->create()->pluck('id')->all();

        $this->seedNoise($userIds, $guestIds, $officerIds);
        $this->seedMatchingPairs($userIds);
    }

    protected function seedNoise(array $userIds, array $guestIds, array $officerIds): void
    {
        Item::factory()
            ->count(self::TOTAL_NOISE_ITEMS)
            ->make()
            ->chunk(self::CHUNK_SIZE)
            ->each(function ($chunk) use ($userIds, $guestIds, $officerIds) {
                $rows = $chunk->map(function (Item $item) use ($userIds, $guestIds, $officerIds) {
                    $isGuest = fake()->boolean(15);
                    $createdAt = $item->date->format('Y-m-d H:i:s');

                    return [
                        'user_id' => $isGuest ? null : Arr::random($userIds),
                        'guest_reporter_id' => $isGuest ? Arr::random($guestIds) : null,
                        'filed_by' => $isGuest ? Arr::random($officerIds) : null,
                        'type' => $item->type,
                        'name' => $item->name,
                        'category' => $item->category,
                        'location' => $item->location,
                        'date' => $item->date->format('Y-m-d'),
                        'description' => $item->description,
                        'contact' => null,
                        'image' => null,
                        'status' => $item->status,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                })->all();

                Item::insert($rows);
            });
    }

    protected function seedMatchingPairs(array $userIds): void
    {
        $matcher = app(MatchingEngine::class);

        foreach (range(1, self::MATCH_PAIRS) as $i) {
            $category = fake()->randomElement(array_keys(ItemFactory::CATALOG));
            $name = fake()->randomElement(ItemFactory::CATALOG[$category]);
            $location = fake()->randomElement(ItemFactory::LOCATIONS);
            $baseDate = fake()->dateTimeBetween('-30 days', '-1 days');
            $closeMatch = fake()->boolean(80); // 80% strong pairs, 20% deliberate near-misses

            $lostReporter = Arr::random($userIds);
            $foundReporter = Arr::random(array_values(array_diff($userIds, [$lostReporter])));

            $lost = Item::create([
                'user_id' => $lostReporter,
                'type' => 'lost',
                'name' => $name,
                'category' => $category,
                'location' => $location,
                'date' => $baseDate->format('Y-m-d'),
                'description' => fake()->sentence(8),
                'status' => 'open',
            ]);

            $found = Item::create([
                'user_id' => $foundReporter,
                'type' => 'found',
                'name' => $closeMatch ? $name : fake()->randomElement(ItemFactory::CATALOG[$category]),
                'category' => $category,
                'location' => $closeMatch ? $location : fake()->randomElement(ItemFactory::LOCATIONS),
                'date' => (clone $baseDate)->modify('+'.($closeMatch ? random_int(0, 2) : random_int(10, 20)).' days')->format('Y-m-d'),
                'description' => fake()->sentence(8),
                'status' => 'open',
            ]);

            $matcher->runFor($found);
        }
    }
}
