<?php

namespace Tests\Feature\Auth;

use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_can_view_public_registration_page(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your customer account')
            ->assertSee('Register as a customer')
            ->assertSee('Already have an account?');
    }

    public function test_guest_can_register_as_a_customer(): void
    {
        $response = $this->post(route('register.store'), $this->validRegistrationData());

        $user = User::where('email', 'new.customer@example.test')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('New Customer', $user->name);
    }

    public function test_new_registered_customer_is_unverified_by_default(): void
    {
        $this->post(route('register.store'), $this->validRegistrationData());

        $user = User::where('email', 'new.customer@example.test')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_public_customer_registration_sends_email_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('register.store'), $this->validRegistrationData());

        $user = User::where('email', 'new.customer@example.test')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registered_user_receives_customer_role(): void
    {
        $this->post(route('register.store'), $this->validRegistrationData());

        $user = User::where('email', 'new.customer@example.test')->firstOrFail();

        $this->assertTrue($user->isCustomer());
        $this->assertFalse($user->isManagement());
        $this->assertFalse($user->isTherapist());
    }

    public function test_customer_profile_is_created_and_linked(): void
    {
        $this->post(route('register.store'), $this->validRegistrationData([
            'name' => 'Mila Santos',
            'email' => 'mila.customer@example.test',
            'phone' => '09181234567',
        ]));

        $user = User::where('email', 'mila.customer@example.test')->firstOrFail();

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Mila',
            'last_name' => 'Santos',
            'email' => 'mila.customer@example.test',
            'phone' => '09181234567',
            'is_active' => true,
        ]);
        $this->assertInstanceOf(CustomerProfile::class, $user->customerProfile);
    }

    public function test_public_registration_ignores_privileged_role_input(): void
    {
        $managementRole = Role::where('name', 'management')->firstOrFail();

        $this->post(route('register.store'), $this->validRegistrationData([
            'role' => 'management',
            'role_id' => $managementRole->id,
            'email' => 'role-attack@example.test',
        ]));

        $user = User::where('email', 'role-attack@example.test')->firstOrFail();

        $this->assertTrue($user->isCustomer());
        $this->assertNotSame($managementRole->id, $user->role_id);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'duplicate@example.test']);

        $this->from(route('register'))
            ->post(route('register.store'), $this->validRegistrationData([
                'email' => 'duplicate@example.test',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), $this->validRegistrationData([
                'password_confirmation' => 'different-password',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_unverified_registered_customer_is_redirected_to_verification_notice_from_customer_dashboard(): void
    {
        $this->post(route('register.store'), $this->validRegistrationData());

        $this->get(route('customer.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_verified_registered_customer_can_access_customer_dashboard(): void
    {
        $this->post(route('register.store'), $this->validRegistrationData());

        $user = User::where('email', 'new.customer@example.test')->firstOrFail();
        $user->markEmailAsVerified();

        $this->actingAs($user->fresh())
            ->get(route('customer.index'))
            ->assertOk();
    }

    public function test_registered_customer_cannot_access_management_dashboard(): void
    {
        $this->post(route('register.store'), $this->validRegistrationData());

        $this->get(route('management.index'))->assertForbidden();
    }

    public function test_registered_customer_cannot_access_therapist_dashboard(): void
    {
        $this->post(route('register.store'), $this->validRegistrationData());

        $this->get(route('therapist.index'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Customer',
            'email' => 'new.customer@example.test',
            'phone' => '09180000000',
            'password' => 'customer-password',
            'password_confirmation' => 'customer-password',
        ], $overrides);
    }
}
