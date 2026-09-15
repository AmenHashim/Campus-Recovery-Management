<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Claim;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Category::create(['name' => 'Electronics', 'icon' => 'ti-device-laptop', 'is_active' => true]);
    }

    protected function student(): User
    {
        return User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
    }

    protected function foundItem(array $attributes = []): Item
    {
        return Item::create(array_merge([
            'user_id' => $this->student()->id,
            'type' => 'found',
            'name' => 'Black Dell Laptop',
            'category' => 'Electronics',
            'location' => 'Library',
            'date' => now()->subDay(),
            'status' => 'open',
        ], $attributes));
    }

    public function test_browse_suggestions_return_items_categories_and_locations(): void
    {
        $this->foundItem();

        $body = $this->actingAs($this->student())
            ->getJson(route('student.items.suggest', ['q' => 'la']))
            ->assertOk()
            ->json();

        $this->assertContains('Black Dell Laptop', array_column($body, 'label'));

        // A category hit carries a filter URL so picking it narrows the list directly.
        $categoryRow = collect(
            $this->actingAs($this->student())->getJson(route('student.items.suggest', ['q' => 'electr']))->json()
        )->firstWhere('meta', 'Filter by category');

        $this->assertNotNull($categoryRow);
        $this->assertStringContainsString('category=Electronics', $categoryRow['url']);

        // …and a location match is offered as a plain search term.
        $locationRow = collect(
            $this->actingAs($this->student())->getJson(route('student.items.suggest', ['q' => 'libra']))->json()
        )->firstWhere('meta', 'Location');

        $this->assertSame('Library', $locationRow['label']);
    }

    public function test_suggestions_need_at_least_two_characters(): void
    {
        $this->foundItem();

        $this->actingAs($this->student())
            ->getJson(route('student.items.suggest', ['q' => 'l']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_my_report_suggestions_are_scoped_to_the_signed_in_user(): void
    {
        $mine = $this->student();
        $this->foundItem(['user_id' => $mine->id, 'name' => 'My Blue Umbrella']);
        $this->foundItem(['name' => 'Someone Elses Umbrella']);

        $labels = array_column(
            $this->actingAs($mine)->getJson(route('student.items.mine.suggest', ['q' => 'umbrella']))->json(),
            'label'
        );

        $this->assertSame(['My Blue Umbrella'], $labels);
    }

    public function test_students_cannot_reach_admin_or_officer_suggestions(): void
    {
        $student = $this->student();

        $this->actingAs($student)->get(route('admin.users.suggest', ['q' => 'ab']))->assertForbidden();
        $this->actingAs($student)->get(route('admin.audit.suggest', ['q' => 'ab']))->assertForbidden();
        $this->actingAs($student)->get(route('officer.claims.suggest', ['q' => 'ab']))->assertForbidden();
    }

    public function test_officer_claim_suggestions_match_token_and_claimant(): void
    {
        $officer = User::factory()->create(['user_type' => null])->assignRole(User::ROLE_OFFICER);
        $claimant = $this->student();

        $claim = Claim::create([
            'item_id' => $this->foundItem()->id,
            'claimant_user_id' => $claimant->id,
            'token' => 'CPRMS-TESTTOKEN',
            'status' => 'pending',
        ]);

        $this->actingAs($officer)
            ->getJson(route('officer.claims.suggest', ['q' => 'TESTTOKEN']))
            ->assertOk()
            ->assertJsonFragment(['label' => $claim->token]);
    }

    public function test_admin_user_suggestions_include_deleted_accounts(): void
    {
        $admin = User::factory()->create(['user_type' => null])->assignRole(User::ROLE_ADMIN);
        $deleted = User::factory()->create(['name' => 'Zawadi Mnyika'])->assignRole(User::ROLE_STUDENT_STAFF);
        $deleted->delete();

        $this->actingAs($admin)
            ->getJson(route('admin.users.suggest', ['q' => 'Zawadi']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Zawadi Mnyika']);
    }
}
