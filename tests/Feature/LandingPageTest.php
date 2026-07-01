<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_can_view_landing_page(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_landing_page_shows_casa_paraiso_branding_and_booking_cta(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Casa Paraiso Body and Wellness Spa')
            ->assertSee('Book an Appointment')
            ->assertSee('Online booking, verified customer accounts, and staff-supported walk-ins.');
    }

    public function test_landing_page_links_to_public_auth_routes_for_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Create Customer Account');
    }

    public function test_landing_page_describes_email_verification_and_booking_flow(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Create Customer Account')
            ->assertSee('Verify your email')
            ->assertSee('Choose your service and time')
            ->assertSee('Visit Casa Paraiso');
    }

    public function test_landing_page_does_not_expose_internal_role_links_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="'.route('management.index').'"', false)
            ->assertDontSee('href="'.route('therapist.index').'"', false)
            ->assertDontSee('href="'.route('customer.index').'"', false)
            ->assertDontSee('Management Dashboard')
            ->assertDontSee('Therapist Dashboard');
    }

    public function test_verified_customer_booking_cta_points_to_customer_booking(): void
    {
        $user = $this->createUserWithRole('customer');

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('customer.appointments.create').'"', false)
            ->assertSee('Book from your verified Casa Paraiso customer account.');
    }

    public function test_login_register_and_verification_pages_still_render(): void
    {
        $unverifiedCustomer = $this->createUserWithRole('customer', verified: false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome back');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your customer account');

        $this->actingAs($unverifiedCustomer)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verify your email address');
    }

    private function createUserWithRole(string $roleName, bool $verified = true): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $factory = User::factory();

        if (! $verified) {
            $factory = $factory->unverified();
        }

        return $factory->create(['role_id' => $role->id]);
    }
}
