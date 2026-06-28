<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DevelopmentUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back');
    }

    public function test_valid_user_can_authenticate_directly_to_role_dashboard(): void
    {
        $role = Role::create([
            'name' => 'management',
            'display_name' => 'Management',
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/management');
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertRedirect('/management');
    }

    public function test_seeded_accounts_can_authenticate_and_redirect_directly_to_their_role_areas(): void
    {
        $this->seed([RoleSeeder::class, DevelopmentUserSeeder::class]);

        $accounts = [
            'management@example.test' => '/management',
            'maya.therapist@example.test' => '/therapist',
            'leo.therapist@example.test' => '/therapist',
            'ana.customer@example.test' => '/customer',
            'miguel.customer@example.test' => '/customer',
        ];

        foreach ($accounts as $email => $destination) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'password',
            ])->assertRedirect($destination);

            $this->assertAuthenticated();
            $this->get('/dashboard')->assertRedirect($destination);

            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $user->email);
        $this->assertGuest();
    }

    public function test_login_requires_a_valid_email_and_password(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => '',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_guests_are_redirected_from_authenticated_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/management')->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_authenticated_user_visiting_login_goes_directly_to_role_dashboard(): void
    {
        $role = Role::create([
            'name' => 'customer',
            'display_name' => 'Customer',
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/customer');
    }
}
