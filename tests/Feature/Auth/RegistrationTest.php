<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'user_type' => User::TYPE_STUDENT,
            'reg_no' => 'UNI/12345',
            'email' => 'test@example.com',
            'password' => 'Str0ng-Passw0rd',
            'password_confirmation' => 'Str0ng-Passw0rd',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('student.dashboard'));

        // BR-07: the role is assigned server-side and can never be elevated by input.
        $this->assertTrue(User::firstWhere('email', 'test@example.com')->isStudentStaff());
    }

    public function test_registration_cannot_assign_a_privileged_role(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky User',
            'user_type' => User::TYPE_STUDENT,
            'reg_no' => 'UNI/54321',
            'email' => 'sneaky@example.com',
            'password' => 'Str0ng-Passw0rd',
            'password_confirmation' => 'Str0ng-Passw0rd',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertFalse(User::firstWhere('email', 'sneaky@example.com')->isAdmin());
    }
}
