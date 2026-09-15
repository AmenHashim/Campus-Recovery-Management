<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboards with data in them — the smoke test only proves they render empty.
 */
class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Category::create(['name' => 'Electronics', 'icon' => 'ti-device-laptop', 'is_active' => true]);

        $this->student = User::factory()->create(['name' => 'Biggie Kasese'])
            ->assignRole(User::ROLE_STUDENT_STAFF);
    }

    protected function item(array $attributes = []): Item
    {
        return Item::create(array_merge([
            'user_id' => $this->student->id,
            'type' => 'found',
            'name' => 'Black Dell Laptop',
            'category' => 'Electronics',
            'location' => 'Library',
            'date' => now()->subDays(2),
            'status' => 'open',
        ], $attributes));
    }

    protected function claim(Item $item, array $attributes = []): Claim
    {
        return Claim::create(array_merge([
            'item_id' => $item->id,
            'claimant_user_id' => $this->student->id,
            'token' => 'CPRMS-QUEUE01',
            'status' => 'pending',
        ], $attributes));
    }

    public function test_student_dashboard_shows_personal_widgets(): void
    {
        $this->item(['name' => 'Silver Water Bottle', 'status' => 'returned']);
        $this->claim($this->item(['name' => 'Blue Backpack']), ['status' => 'verified', 'token' => 'CPRMS-STUD01']);

        // Somebody else's find should surface in "Recently handed in".
        $other = User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
        $this->item(['user_id' => $other->id, 'name' => 'Red Umbrella']);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Silver Water Bottle')   // my reports table
            ->assertSee('CPRMS-STUD01')          // my claims table
            ->assertSee('My reports by status')  // personal chart
            ->assertSee('Red Umbrella');         // found pool

        // Recovered = my returned find + my verified claim.
        $response->assertSee('Items Recovered');
        $this->assertSame(2, $response->viewData('itemsRecovered'));
    }

    public function test_student_dashboard_hides_the_users_own_reports_from_the_found_pool(): void
    {
        $this->item(['name' => 'My Own Find']);

        $latest = $this->actingAs($this->student)
            ->get(route('student.dashboard'))
            ->viewData('latestFound');

        $this->assertTrue($latest->isEmpty());
    }

    public function test_officer_dashboard_flags_overdue_claims_and_aging_storage(): void
    {
        $officer = User::factory()->create(['user_type' => null])->assignRole(User::ROLE_OFFICER);

        $fresh = $this->claim($this->item(), ['token' => 'CPRMS-FRESH1']);
        $stale = $this->claim($this->item(['name' => 'Old Claim Item']), ['token' => 'CPRMS-STALE1']);
        $stale->forceFill(['created_at' => now()->subDays(9)])->save();

        $this->item(['name' => 'Ancient Wallet', 'date' => now()->subDays(120)]);

        $response = $this->actingAs($officer)->get(route('officer.dashboard'));

        $response->assertOk()
            ->assertSee('CPRMS-FRESH1')
            ->assertSee('CPRMS-STALE1')
            ->assertSee('Ancient Wallet')
            ->assertSee('Claims awaiting verification');

        $this->assertSame(1, $response->viewData('overdueClaims'));
        $this->assertSame(1, $response->viewData('agingItems'));
        $this->assertArrayHasKey('Electronics', $response->viewData('storageByCategory'));
    }

    public function test_admin_dashboard_shows_population_activity_and_rates(): void
    {
        $admin = User::factory()->create(['user_type' => null])->assignRole(User::ROLE_ADMIN);

        $this->item(['status' => 'returned']);
        $this->item(['name' => 'Still Open Item']);
        $this->claim($this->item(['name' => 'Claimed Item']), ['status' => 'verified', 'token' => 'CPRMS-ADM001']);

        AuditLog::record('item.returned', 'Marked "Black Dell Laptop" as returned', null, $admin->id);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Accounts by role')
            ->assertSee('Marked &quot;Black Dell Laptop&quot; as returned', false)
            ->assertSee('Biggie Kasese');       // newest accounts table

        // One of three found items returned.
        $this->assertSame(33.3, $response->viewData('recoveryRate'));
        $this->assertSame(100.0, $response->viewData('approvalRate'));
        $byRole = $response->viewData('usersByRole');
        $this->assertSame(1, $byRole['Students'] + $byRole['Staff']);
        $this->assertSame(1, $byRole['Admins']);
    }

    public function test_admin_dashboard_counts_deleted_accounts_separately(): void
    {
        $admin = User::factory()->create(['user_type' => null])->assignRole(User::ROLE_ADMIN);
        User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF)->delete();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $this->assertSame(1, $response->viewData('deletedUsers'));
        // The headline count excludes them.
        $this->assertSame(2, $response->viewData('totalUsers'));
    }
}
