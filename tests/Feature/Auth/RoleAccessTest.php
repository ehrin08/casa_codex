<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_role_pages(): void
    {
        foreach (['/management', '/therapist', '/customer'] as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }

    public function test_management_user_can_only_access_management_area(): void
    {
        $user = $this->createUserWithRole('management');

        $this->actingAs($user)->get('/management')->assertOk();
        $this->get('/therapist')->assertForbidden();
        $this->get('/customer')->assertForbidden();
    }

    public function test_therapist_user_can_only_access_therapist_area(): void
    {
        $user = $this->createUserWithRole('therapist');

        $this->actingAs($user)->get('/therapist')->assertOk();
        $this->get('/management')->assertForbidden();
        $this->get('/customer')->assertForbidden();
    }

    public function test_customer_user_can_only_access_customer_area(): void
    {
        $user = $this->createUserWithRole('customer');

        $this->actingAs($user)->get('/customer')->assertOk();
        $this->get('/management')->assertForbidden();
        $this->get('/therapist')->assertForbidden();
    }

    public function test_dashboard_redirects_each_role_to_its_area(): void
    {
        $destinations = [
            'management' => '/management',
            'therapist' => '/therapist',
            'customer' => '/customer',
        ];

        foreach ($destinations as $role => $destination) {
            $user = $this->createUserWithRole($role);

            $this->actingAs($user)->get('/dashboard')->assertRedirect($destination);
        }
    }

    public function test_login_redirects_each_role_through_dashboard_to_its_area(): void
    {
        $destinations = [
            'management' => '/management',
            'therapist' => '/therapist',
            'customer' => '/customer',
        ];

        foreach ($destinations as $role => $destination) {
            $user = $this->createUserWithRole($role);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect('/dashboard');

            $this->get('/dashboard')->assertRedirect($destination);
            $this->post('/logout');
        }
    }

    public function test_navigation_only_shows_the_authenticated_users_role_area(): void
    {
        $routes = [
            'management' => route('management.index'),
            'therapist' => route('therapist.index'),
            'customer' => route('customer.index'),
        ];

        $roleNavigation = [
            'management' => [
                'management.services.index',
                'management.therapists.index',
                'management.customers.index',
                'management.availability.index',
                'management.appointments.index',
                'management.transactions.index',
                'management.commissions.index',
            ],
            'therapist' => [
                'therapist.schedule.index',
                'therapist.commissions.index',
            ],
            'customer' => [
                'customer.appointments.create',
                'customer.appointments.index',
            ],
        ];

        foreach ($routes as $role => $visibleRoute) {
            $user = $this->createUserWithRole($role);
            $response = $this->actingAs($user)->get($visibleRoute);

            $response
                ->assertOk()
                ->assertSee('action="'.route('logout').'"', false)
                ->assertSee('aria-label="Account navigation"', false)
                ->assertSee('id="mobile-account-navigation"', false)
                ->assertSee('href="'.route('notifications.index').'"', false)
                ->assertDontSee('href="'.route('login').'"', false);

            foreach ($roleNavigation[$role] as $navigationRoute) {
                $response->assertSee('href="'.route($navigationRoute).'"', false);
            }

            foreach ($routes as $candidateRole => $candidateRoute) {
                if ($candidateRole === $role) {
                    $response->assertSee('href="'.$candidateRoute.'"', false);
                } else {
                    $response->assertDontSee('href="'.$candidateRoute.'"', false);
                }
            }
        }
    }

    public function test_guest_navigation_shows_home_and_login_only(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('href="'.route('management.index').'"', false)
            ->assertDontSee('href="'.route('therapist.index').'"', false)
            ->assertDontSee('href="'.route('customer.index').'"', false)
            ->assertDontSee('action="'.route('logout').'"', false);
    }

    public function test_user_role_helpers_match_the_assigned_role(): void
    {
        $user = $this->createUserWithRole('therapist');

        $this->assertTrue($user->hasRole('therapist'));
        $this->assertTrue($user->isTherapist());
        $this->assertFalse($user->isManagement());
        $this->assertFalse($user->isCustomer());
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }
}
