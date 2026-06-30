<?php

namespace Tests\Feature\Management;

use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\TherapistProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_management_can_create_customer_account_with_profile(): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)
            ->post(route('management.customers.store'), [
                'create_account' => '1',
                'user_id' => null,
                'first_name' => 'Account',
                'last_name' => 'Customer',
                'email' => 'account.customer@example.test',
                'phone' => '09180000111',
                'birth_date' => null,
                'gender' => null,
                'address' => 'Bacolod City',
                'notes' => 'Created with account.',
                'is_active' => true,
                'account_password' => 'customer-password',
                'account_password_confirmation' => 'customer-password',
            ])
            ->assertRedirect(route('management.customers.index'))
            ->assertSessionHas('success');

        $user = User::where('email', 'account.customer@example.test')->firstOrFail();
        $profile = CustomerProfile::where('email', 'account.customer@example.test')->firstOrFail();

        $this->assertTrue($user->isCustomer());
        $this->assertSame($user->id, $profile->user_id);
        $this->assertTrue(Hash::check('customer-password', $user->password));
    }

    public function test_management_can_create_therapist_account_with_profile(): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)
            ->post(route('management.therapists.store'), [
                'create_account' => '1',
                'user_id' => null,
                'employee_code' => 'CP-T900',
                'first_name' => 'Account',
                'last_name' => 'Therapist',
                'email' => 'account.therapist@example.test',
                'phone' => '09170000111',
                'specialty' => 'Deep tissue massage',
                'commission_rate' => 18.5,
                'status' => 'active',
                'hired_at' => '2026-06-15',
                'notes' => 'Created with account.',
                'account_password' => 'therapist-password',
                'account_password_confirmation' => 'therapist-password',
            ])
            ->assertRedirect(route('management.therapists.index'))
            ->assertSessionHas('success');

        $user = User::where('email', 'account.therapist@example.test')->firstOrFail();
        $profile = TherapistProfile::where('employee_code', 'CP-T900')->firstOrFail();

        $this->assertTrue($user->isTherapist());
        $this->assertSame($user->id, $profile->user_id);
        $this->assertTrue(Hash::check('therapist-password', $user->password));
    }

    public function test_management_can_create_account_for_existing_unlinked_customer_profile(): void
    {
        $manager = $this->createUserWithRole('management');
        $profile = CustomerProfile::create([
            'first_name' => 'Existing',
            'last_name' => 'Guest',
            'email' => 'existing.customer@example.test',
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->put(route('management.customers.update', $profile), [
                'create_account' => '1',
                'user_id' => null,
                'first_name' => 'Existing',
                'last_name' => 'Guest',
                'email' => 'existing.customer@example.test',
                'phone' => null,
                'birth_date' => null,
                'gender' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
                'account_password' => 'customer-password',
                'account_password_confirmation' => 'customer-password',
            ])
            ->assertRedirect(route('management.customers.index'));

        $user = User::where('email', 'existing.customer@example.test')->firstOrFail();

        $this->assertTrue($user->isCustomer());
        $this->assertSame($user->id, $profile->fresh()->user_id);
    }

    public function test_non_management_users_cannot_create_management_owned_accounts(): void
    {
        foreach (['customer', 'therapist'] as $roleName) {
            $user = $this->createUserWithRole($roleName);

            $this->actingAs($user)
                ->post(route('management.customers.store'), [
                    'create_account' => '1',
                    'first_name' => 'Blocked',
                    'email' => "blocked-customer-{$roleName}@example.test",
                    'is_active' => true,
                    'account_password' => 'customer-password',
                    'account_password_confirmation' => 'customer-password',
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('management.therapists.store'), [
                    'create_account' => '1',
                    'first_name' => 'Blocked',
                    'email' => "blocked-therapist-{$roleName}@example.test",
                    'commission_rate' => 10,
                    'status' => 'active',
                    'account_password' => 'therapist-password',
                    'account_password_confirmation' => 'therapist-password',
                ])
                ->assertForbidden();
        }
    }

    public function test_management_account_creation_rejects_duplicate_user_email(): void
    {
        $manager = $this->createUserWithRole('management');
        User::factory()->create(['email' => 'duplicate.account@example.test']);

        $this->actingAs($manager)
            ->from(route('management.customers.create'))
            ->post(route('management.customers.store'), [
                'create_account' => '1',
                'first_name' => 'Duplicate',
                'email' => 'duplicate.account@example.test',
                'is_active' => true,
                'account_password' => 'customer-password',
                'account_password_confirmation' => 'customer-password',
            ])
            ->assertRedirect(route('management.customers.create'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('customer_profiles', [
            'first_name' => 'Duplicate',
            'email' => 'duplicate.account@example.test',
        ]);
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
