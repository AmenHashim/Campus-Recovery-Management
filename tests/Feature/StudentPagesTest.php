<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Category::create(['name' => 'Electronics', 'icon' => 'ti-device-laptop', 'is_active' => true]);
        $this->user = User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
    }

    protected function item(array $attributes = []): Item
    {
        return Item::create(array_merge([
            'user_id' => $this->user->id,
            'type' => 'found',
            'name' => 'Black Dell Laptop',
            'category' => 'Electronics',
            'location' => 'Library',
            'date' => now()->subDay(),
            'status' => 'open',
        ], $attributes));
    }

    public function test_dashboard_shows_recent_reports_and_claims(): void
    {
        $item = $this->item(['name' => 'Silver Water Bottle']);
        Claim::create([
            'item_id' => $item->id,
            'claimant_user_id' => $this->user->id,
            'token' => 'CPRMS-ABC123',
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Silver Water Bottle')
            ->assertSee('CPRMS-ABC123')
            ->assertDontSee('Next sprint');
    }

    public function test_my_reports_can_be_searched_and_filtered_by_type(): void
    {
        $this->item(['name' => 'Blue Backpack', 'type' => 'lost']);
        $this->item(['name' => 'Silver Water Bottle', 'type' => 'found']);

        $this->actingAs($this->user)
            ->get(route('student.items.mine', ['q' => 'backpack']))
            ->assertOk()
            ->assertSee('Blue Backpack')
            ->assertDontSee('Silver Water Bottle');

        $this->actingAs($this->user)
            ->get(route('student.items.mine', ['type' => 'found']))
            ->assertOk()
            ->assertSee('Silver Water Bottle')
            ->assertDontSee('Blue Backpack');
    }

    public function test_browse_search_also_matches_location_and_category(): void
    {
        $this->item(['name' => 'Silver Water Bottle', 'location' => 'Cafeteria', 'user_id' => null]);

        $this->actingAs($this->user)
            ->get(route('student.items.index', ['q' => 'cafeteria']))
            ->assertOk()
            ->assertSee('Silver Water Bottle');
    }

    public function test_my_claims_shows_the_token_and_the_rejection_reason(): void
    {
        Claim::create([
            'item_id' => $this->item()->id,
            'claimant_user_id' => $this->user->id,
            'token' => 'CPRMS-XYZ789',
            'status' => 'rejected',
            'verified_at' => now(),
            'notes' => 'Could not describe the contents.',
        ]);

        $this->actingAs($this->user)
            ->get(route('student.claims.index'))
            ->assertOk()
            ->assertSee('CPRMS-XYZ789')
            ->assertSee('Could not describe the contents.');
    }

    public function test_user_can_mark_all_notifications_read(): void
    {
        AppNotification::create([
            'user_id' => $this->user->id,
            'type' => 'match_found',
            'title' => 'Possible match',
            'message' => 'We found something similar to your report.',
        ]);

        $this->actingAs($this->user)
            ->post(route('student.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $this->user->appNotifications()->unread()->count());
    }
}
