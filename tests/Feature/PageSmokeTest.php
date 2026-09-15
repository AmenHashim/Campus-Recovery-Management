<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every dashboard page renders for its role. Cheap insurance against a Blade
 * break in the shared layout, the search bar, or a row action menu.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function userWithRole(string $role): User
    {
        $type = $role === User::ROLE_STUDENT_STAFF ? User::TYPE_STUDENT : null;

        return User::factory()->create(['user_type' => $type])->assignRole($role);
    }

    public static function pageProvider(): array
    {
        return [
            'student dashboard' => [User::ROLE_STUDENT_STAFF, 'student.dashboard'],
            'student report' => [User::ROLE_STUDENT_STAFF, 'student.items.create'],
            'student browse' => [User::ROLE_STUDENT_STAFF, 'student.items.index'],
            'student my reports' => [User::ROLE_STUDENT_STAFF, 'student.items.mine'],
            'student claims' => [User::ROLE_STUDENT_STAFF, 'student.claims.index'],
            'student notifications' => [User::ROLE_STUDENT_STAFF, 'student.notifications.index'],

            'officer dashboard' => [User::ROLE_OFFICER, 'officer.dashboard'],
            'officer guest report' => [User::ROLE_OFFICER, 'officer.guest-reports.create'],
            'officer intake' => [User::ROLE_OFFICER, 'officer.intake.index'],
            'officer claims' => [User::ROLE_OFFICER, 'officer.claims.index'],
            'officer notifications' => [User::ROLE_OFFICER, 'officer.notifications.index'],

            'admin dashboard' => [User::ROLE_ADMIN, 'admin.dashboard'],
            'admin users' => [User::ROLE_ADMIN, 'admin.users.index'],
            'admin reference' => [User::ROLE_ADMIN, 'admin.reference.index'],
            'admin analytics' => [User::ROLE_ADMIN, 'admin.analytics.index'],
            'admin audit' => [User::ROLE_ADMIN, 'admin.audit.index'],

            'profile' => [User::ROLE_STUDENT_STAFF, 'profile.edit'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pageProvider')]
    public function test_page_renders(string $role, string $route): void
    {
        $this->actingAs($this->userWithRole($role))
            ->get(route($route))
            ->assertOk();
    }
}
