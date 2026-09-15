<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Seeds the managed category/location lists (FR-F2). Icons mirror the fallback map in
 * Item::categoryIcon(); locations mirror the pool used by ItemFactory, so the seeded
 * items all reference a managed reference value.
 */
class ReferenceDataSeeder extends Seeder
{
    protected const CATEGORIES = [
        'Electronics' => 'ti-device-mobile',
        'Documents' => 'ti-file-text',
        'Clothing' => 'ti-shirt',
        'Bags' => 'ti-backpack',
        'Keys' => 'ti-key',
        'Accessories' => 'ti-watch',
        'Books' => 'ti-book',
        'Other' => 'ti-box',
    ];

    protected const LOCATIONS = [
        'Main Library', 'Cafeteria', 'Lecture Hall A', 'Lecture Hall B', 'Hostel Block C',
        'Parking Lot', 'Main Gate', 'ICT Lab', 'Sports Complex', 'Admin Block',
        'Theatre A', 'Theatre B', 'Chemistry Lab', 'Student Center',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name => $icon) {
            Category::firstOrCreate(['name' => $name], ['icon' => $icon, 'is_active' => true]);
        }

        foreach (self::LOCATIONS as $name) {
            Location::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
