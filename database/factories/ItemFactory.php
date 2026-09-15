<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 *
 * Reporter (user_id / guest_reporter_id / filed_by) is deliberately left unset here —
 * it depends on an already-seeded pool of users/guests, so ItemSeeder assigns it.
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public const CATALOG = [
        'Electronics' => ['Black HP Laptop', 'Samsung Galaxy Phone', 'iPhone 13', 'Wireless Earbuds', 'Laptop Charger', 'Bluetooth Speaker', 'Power Bank', 'Casio Calculator'],
        'Documents' => ['Student ID Card', 'National ID', 'Birth Certificate', 'Admission Letter', 'Exam Card', 'Passport'],
        'Clothing' => ['Blue Hoodie', 'Black Jacket', 'Grey Sweater', 'Sports Jersey', 'Scarf'],
        'Bags' => ['Black Backpack', 'Leather Handbag', 'Laptop Bag', 'Gym Bag', 'Drawstring Bag'],
        'Keys' => ['Bunch of House Keys', 'Car Key (Toyota)', 'Padlock Key', 'Motorbike Key'],
        'Accessories' => ['Wrist Watch', 'Sunglasses', 'Silver Necklace', 'Umbrella', 'Wallet', 'Spectacles'],
        'Books' => ['Accounting Textbook', 'Statistics Notebook', 'Novel: Things Fall Apart', 'Lecture Notes Folder'],
        'Other' => ['Water Bottle', 'USB Flash Drive', 'Charger Cable', 'Face Mask Box'],
    ];

    public const LOCATIONS = [
        'Main Library', 'Cafeteria', 'Lecture Hall A', 'Lecture Hall B', 'Hostel Block C',
        'Parking Lot', 'Main Gate', 'ICT Lab', 'Sports Complex', 'Admin Block',
        'Theatre A', 'Theatre B', 'Chemistry Lab', 'Student Center',
    ];

    public function definition(): array
    {
        $category = fake()->randomElement(array_keys(self::CATALOG));

        return [
            'type' => fake()->randomElement(['lost', 'found']),
            'name' => fake()->randomElement(self::CATALOG[$category]),
            'category' => $category,
            'location' => fake()->randomElement(self::LOCATIONS),
            'date' => fake()->dateTimeBetween('-60 days', 'now'),
            'description' => fake()->sentence(10),
            'contact' => null,
            'image' => null,
            // 'claimed'/'returned' only ever happen through a real Claim row (see ClaimSeeder) —
            // bulk noise data stays in the states that don't require one.
            'status' => self::weighted(['open' => 60, 'matched' => 25, 'closed' => 15]),
        ];
    }

    /** Shared weighted-random helper — also used by ClaimSeeder for resolution outcomes. */
    public static function weighted(array $weights): string
    {
        $rand = random_int(1, array_sum($weights));

        foreach ($weights as $key => $weight) {
            if ($rand <= $weight) {
                return $key;
            }
            $rand -= $weight;
        }

        return array_key_first($weights);
    }
}
