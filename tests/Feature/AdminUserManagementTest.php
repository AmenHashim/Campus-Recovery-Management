<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        return User::factory()->create(['user_type' => null])->assignRole(User::ROLE_ADMIN);
    }

    protected function member(): User
    {
        return User::factory()->create()->assignRole(User::ROLE_STUDENT_STAFF);
    }

    public function test_admin_can_soft_delete_a_user(): void
    {
        $admin = $this->admin();
        $user = $this->member();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect();

        // Gone from the app…
        $this->assertNull(User::find($user->id));
        // …but still in the database.
        $this->assertNotNull(User::withTrashed()->find($user->id));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'reg_no' => $user->reg_no]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertNotNull(User::find($admin->id));
    }

    public function test_a_deleted_user_cannot_log_in(): void
    {
        $user = $this->member();
        $user->delete();

        $this->post('/login', ['login' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_admin_can_restore_a_deleted_user(): void
    {
        $admin = $this->admin();
        $user = $this->member();
        $user->delete();

        $this->actingAs($admin)
            ->post(route('admin.users.restore', $user->id))
            ->assertRedirect();

        $this->assertNotNull(User::find($user->id));
    }

    public function test_non_admins_cannot_delete_accounts(): void
    {
        $actor = $this->member();
        $target = $this->member();

        $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertNotNull(User::find($target->id));
    }
}
