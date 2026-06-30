<?php

namespace Tests\Feature\Auth;

use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_unverified_customer_can_view_verification_notice(): void
    {
        $user = $this->createUserWithRole('customer', verified: false);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verify your email address')
            ->assertSee('We sent a verification link to your email.')
            ->assertSee('Please verify your email before booking an appointment.')
            ->assertSee('Resend verification email')
            ->assertSee('Back to login');
    }

    public function test_unverified_customer_is_redirected_from_customer_dashboard_to_verification_notice(): void
    {
        $user = $this->createUserWithRole('customer', verified: false);

        $this->actingAs($user)
            ->get(route('customer.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_customer_is_redirected_from_booking_page_to_verification_notice(): void
    {
        $user = $this->createUserWithRole('customer', verified: false);

        $this->actingAs($user)
            ->get(route('customer.appointments.create'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_customer_can_access_customer_dashboard(): void
    {
        $user = $this->createUserWithRole('customer');

        $this->actingAs($user)
            ->get(route('customer.index'))
            ->assertOk();
    }

    public function test_verified_customer_can_access_appointment_booking(): void
    {
        [$user] = $this->createCustomer();

        $this->actingAs($user)
            ->get(route('customer.appointments.create'))
            ->assertOk();
    }

    public function test_verification_link_successfully_marks_the_user_as_verified(): void
    {
        $user = $this->createUserWithRole('customer', verified: false);
        $url = $this->verificationUrlFor($user);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('customer.index'))
            ->assertSessionHas('success', 'Your email has been verified.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_invalid_signed_verification_link_redirects_to_notice_with_feedback(): void
    {
        $user = $this->createUserWithRole('customer', verified: false);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => 'invalid-hash',
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unsigned_verification_link_redirects_to_notice_with_feedback(): void
    {
        $user = $this->createUserWithRole('customer', verified: false);

        $this->actingAs($user)
            ->get(route('verification.verify', [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_verification_email_works_for_unverified_user(): void
    {
        Notification::fake();
        $user = $this->createUserWithRole('customer', verified: false);

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_already_verified_user_is_redirected_away_from_verification_notice(): void
    {
        $user = $this->createUserWithRole('customer');

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('customer.index'))
            ->assertSessionHas('success', 'Your email is already verified.');
    }

    public function test_management_users_still_access_management_dashboard_without_email_verification(): void
    {
        $user = $this->createUserWithRole('management', verified: false);

        $this->actingAs($user)
            ->get(route('management.index'))
            ->assertOk();
    }

    public function test_therapist_users_still_access_therapist_dashboard_without_email_verification(): void
    {
        $user = $this->createUserWithRole('therapist', verified: false);

        $this->actingAs($user)
            ->get(route('therapist.index'))
            ->assertOk();
    }

    private function verificationUrlFor(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }

    /**
     * @return array{User, CustomerProfile}
     */
    private function createCustomer(): array
    {
        $user = $this->createUserWithRole('customer');
        $profile = CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Verified',
            'last_name' => 'Customer',
            'email' => $user->email,
            'is_active' => true,
        ]);

        return [$user, $profile];
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
