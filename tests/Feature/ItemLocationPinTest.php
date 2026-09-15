<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The map pin is optional everywhere. These cover the two things that could quietly
 * break: a pin must survive the round-trip, and a half-filled pair must not.
 */
class ItemLocationPinTest extends TestCase
{
    use RefreshDatabase;

    protected function student(): User
    {
        $this->seed(RoleSeeder::class);

        return User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
    }

    protected function payload(array $overrides = []): array
    {
        Category::firstOrCreate(['name' => 'Electronics'], ['is_active' => true]);

        return array_merge([
            'type' => 'lost',
            'name' => 'Black Samsung phone',
            'category' => 'Electronics',
            'location' => 'Main Library',
            'date' => now()->toDateString(),
        ], $overrides);
    }

    public function test_a_report_can_be_submitted_without_a_pin(): void
    {
        $this->actingAs($this->student())
            ->post(route('student.items.store'), $this->payload())
            ->assertRedirect();

        $item = Item::firstOrFail();
        $this->assertNull($item->latitude);
        $this->assertNull($item->longitude);
        $this->assertFalse($item->hasPin());
    }

    public function test_a_dropped_pin_is_stored_with_the_report(): void
    {
        $this->actingAs($this->student())
            ->post(route('student.items.store'), $this->payload([
                'latitude' => '-6.8161234',
                'longitude' => '39.2894567',
            ]))
            ->assertRedirect();

        $item = Item::firstOrFail();
        $this->assertTrue($item->hasPin());
        $this->assertEqualsWithDelta(-6.8161234, $item->latitude, 0.0000001);
        $this->assertEqualsWithDelta(39.2894567, $item->longitude, 0.0000001);
    }

    public function test_half_a_coordinate_pair_is_rejected(): void
    {
        $this->actingAs($this->student())
            ->post(route('student.items.store'), $this->payload(['latitude' => '-6.8161234']))
            ->assertSessionHasErrors('longitude');

        $this->assertDatabaseCount('items', 0);
    }

    public function test_out_of_range_coordinates_are_rejected(): void
    {
        $this->actingAs($this->student())
            ->post(route('student.items.store'), $this->payload([
                'latitude' => '120',      // beyond the poles
                'longitude' => '39.28',
            ]))
            ->assertSessionHasErrors('latitude');

        $this->assertDatabaseCount('items', 0);
    }

    public function test_the_report_form_and_reference_page_render(): void
    {
        Category::firstOrCreate(['name' => 'Electronics'], ['is_active' => true]);
        Location::firstOrCreate(['name' => 'Main Library'], [
            'latitude' => -6.8161234, 'longitude' => 39.2894567, 'is_active' => true,
        ]);

        $this->seed(RoleSeeder::class);

        $student = User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
        $this->actingAs($student)->get(route('student.items.create'))
            ->assertOk()->assertSee('Main Library', false);

        $admin = User::factory()->create(['user_type' => null])->assignRole(User::ROLE_ADMIN);
        $this->actingAs($admin)->get(route('admin.reference.index'))
            ->assertOk()
            ->assertSee('Item Categories', false)
            ->assertSee('Campus Locations', false)
            ->assertSee('pinned on map', false);
    }

    public function test_a_managed_location_can_carry_coordinates(): void
    {
        $location = Location::create([
            'name' => 'Main Library',
            'latitude' => -6.8161234,
            'longitude' => 39.2894567,
            'is_active' => true,
        ]);

        $this->assertTrue($location->fresh()->hasPin());
        $this->assertFalse(Location::create(['name' => 'Cafeteria', 'is_active' => true])->hasPin());
    }
}
