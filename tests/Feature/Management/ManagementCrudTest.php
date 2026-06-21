<?php

namespace Tests\Feature\Management;

use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_user_can_access_management_crud_pages(): void
    {
        $manager = $this->createUserWithRole('management');
        $service = Service::create([
            'name' => 'Test Service',
            'duration_minutes' => 60,
            'price' => 500,
            'status' => 'active',
        ]);
        $therapist = TherapistProfile::create([
            'first_name' => 'Test',
            'commission_rate' => 10,
            'status' => 'active',
        ]);
        $customer = CustomerProfile::create([
            'first_name' => 'Test',
            'is_active' => true,
        ]);
        $availability = TherapistAvailability::create([
            'therapist_profile_id' => $therapist->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'active',
        ]);

        $routes = [
            route('management.services.index'),
            route('management.services.create'),
            route('management.services.edit', $service),
            route('management.therapists.index'),
            route('management.therapists.create'),
            route('management.therapists.edit', $therapist),
            route('management.customers.index'),
            route('management.customers.create'),
            route('management.customers.edit', $customer),
            route('management.availability.index'),
            route('management.availability.create'),
            route('management.availability.edit', $availability),
        ];

        foreach ($routes as $route) {
            $this->actingAs($manager)->get($route)->assertOk();
        }
    }

    public function test_management_crud_indexes_render_modal_interactions_and_full_page_fallbacks(): void
    {
        $manager = $this->createUserWithRole('management');
        $service = Service::create([
            'name' => 'Modal Service',
            'duration_minutes' => 60,
            'price' => 750,
            'status' => 'active',
        ]);
        $therapist = TherapistProfile::create([
            'first_name' => 'Modal',
            'last_name' => 'Therapist',
            'commission_rate' => 15,
            'status' => 'active',
        ]);
        $customer = CustomerProfile::create([
            'first_name' => 'Modal',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $availability = TherapistAvailability::create([
            'therapist_profile_id' => $therapist->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'active',
        ]);

        $modules = [
            'service' => [
                route('management.services.index'),
                route('management.services.create'),
                route('management.services.edit', $service),
            ],
            'therapist' => [
                route('management.therapists.index'),
                route('management.therapists.create'),
                route('management.therapists.edit', $therapist),
            ],
            'customer' => [
                route('management.customers.index'),
                route('management.customers.create'),
                route('management.customers.edit', $customer),
            ],
            'availability' => [
                route('management.availability.index'),
                route('management.availability.create'),
                route('management.availability.edit', $availability),
            ],
        ];

        foreach ($modules as $module => [$indexRoute, $createRoute, $editRoute]) {
            $this->actingAs($manager)
                ->get($indexRoute)
                ->assertOk()
                ->assertSee('id="'.$module.'-create-modal"', false)
                ->assertSee('id="'.$module.'-edit-modal"', false)
                ->assertSee('id="'.$module.'-detail-modal"', false)
                ->assertSee('id="'.$module.'-status-modal"', false)
                ->assertSee('href="'.$createRoute.'"', false)
                ->assertSee('href="'.$editRoute.'"', false)
                ->assertSee('data-record-form', false)
                ->assertSee('data-confirm-modal="'.$module.'-status-modal"', false);
        }
    }

    public function test_failed_modal_validation_reopens_the_form_with_old_input_and_accessible_errors(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)
            ->followingRedirects()
            ->from(route('management.services.index'))
            ->post(route('management.services.store'), [
                '_modal' => 'service-create',
                'name' => 'Incomplete Modal Service',
            ]);

        $response
            ->assertOk()
            ->assertSee('id="service-create-modal"', false)
            ->assertSee('data-modal-open-on-load', false)
            ->assertSee('value="Incomplete Modal Service"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('The duration minutes field is required.');
    }

    public function test_guests_are_redirected_from_management_crud_pages(): void
    {
        foreach ($this->moduleIndexRoutes() as $route) {
            $this->get($route)->assertRedirect('/login');
        }

        $this->post(route('management.services.store'), [])->assertRedirect('/login');
    }

    public function test_therapist_and_customer_users_cannot_access_management_crud_pages(): void
    {
        foreach (['therapist', 'customer'] as $role) {
            $user = $this->createUserWithRole($role);

            foreach ($this->moduleIndexRoutes() as $route) {
                $this->actingAs($user)->get($route)->assertForbidden();
            }

            $this->post(route('management.services.store'), [])->assertForbidden();
        }
    }

    public function test_management_user_can_create_update_and_toggle_a_service(): void
    {
        $manager = $this->createUserWithRole('management');
        $category = ServiceCategory::create([
            'name' => 'Massage',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($manager)->post(route('management.services.store'), [
            'service_category_id' => $category->id,
            'name' => 'Aromatherapy Massage',
            'description' => 'Relaxing essential oil massage.',
            'duration_minutes' => 60,
            'price' => 850,
            'status' => 'active',
        ])->assertRedirect(route('management.services.index'))
            ->assertSessionHas('success');

        $service = Service::where('name', 'Aromatherapy Massage')->firstOrFail();
        $this->assertSame('active', $service->status);

        $this->put(route('management.services.update', $service), [
            'service_category_id' => $category->id,
            'name' => 'Premium Aromatherapy Massage',
            'description' => 'Updated description.',
            'duration_minutes' => 90,
            'price' => 1100,
            'status' => 'active',
        ])->assertRedirect(route('management.services.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Premium Aromatherapy Massage',
            'duration_minutes' => 90,
            'price' => 1100,
        ]);

        $this->patch(route('management.services.toggle-status', $service))
            ->assertSessionHas('success');
        $this->assertSame('inactive', $service->fresh()->status);
    }

    public function test_management_user_can_create_update_and_toggle_a_therapist_profile(): void
    {
        $manager = $this->createUserWithRole('management');
        $therapistUser = $this->createUserWithRole('therapist');

        $this->actingAs($manager)->post(route('management.therapists.store'), [
            'user_id' => $therapistUser->id,
            'employee_code' => 'CP-T100',
            'first_name' => 'Jamie',
            'last_name' => 'Dela Cruz',
            'email' => 'jamie@example.test',
            'phone' => '09170000100',
            'specialty' => 'Swedish massage',
            'commission_rate' => 18.5,
            'status' => 'active',
            'hired_at' => '2026-06-01',
            'notes' => 'New therapist profile.',
        ])->assertRedirect(route('management.therapists.index'))
            ->assertSessionHas('success');

        $therapist = TherapistProfile::where('employee_code', 'CP-T100')->firstOrFail();
        $this->assertSame($therapistUser->id, $therapist->user_id);

        $this->put(route('management.therapists.update', $therapist), [
            'user_id' => $therapistUser->id,
            'employee_code' => 'CP-T100',
            'first_name' => 'Jamie',
            'last_name' => 'Santos',
            'email' => 'jamie.santos@example.test',
            'phone' => '09170000101',
            'specialty' => 'Swedish and deep tissue massage',
            'commission_rate' => 20,
            'status' => 'active',
            'hired_at' => '2026-06-01',
            'notes' => 'Updated therapist profile.',
        ])->assertRedirect(route('management.therapists.index'));

        $this->assertDatabaseHas('therapist_profiles', [
            'id' => $therapist->id,
            'last_name' => 'Santos',
            'commission_rate' => 20,
        ]);

        $this->patch(route('management.therapists.toggle-status', $therapist));
        $this->assertSame('inactive', $therapist->fresh()->status);
    }

    public function test_management_user_can_create_update_and_toggle_a_walk_in_customer_profile(): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)->post(route('management.customers.store'), [
            'user_id' => null,
            'first_name' => 'Walk-in',
            'last_name' => 'Guest',
            'email' => 'walkin.new@example.test',
            'phone' => '09180000100',
            'birth_date' => '1995-05-10',
            'gender' => 'female',
            'address' => 'Bacolod City',
            'notes' => 'Walk-in profile.',
            'is_active' => true,
        ])->assertRedirect(route('management.customers.index'))
            ->assertSessionHas('success');

        $customer = CustomerProfile::where('email', 'walkin.new@example.test')->firstOrFail();
        $this->assertNull($customer->user_id);

        $this->put(route('management.customers.update', $customer), [
            'user_id' => null,
            'first_name' => 'Walk-in',
            'last_name' => 'Customer',
            'email' => 'walkin.updated@example.test',
            'phone' => '09180000101',
            'birth_date' => '1995-05-10',
            'gender' => 'prefer_not_to_say',
            'address' => 'Iloilo City',
            'notes' => 'Updated walk-in profile.',
            'is_active' => true,
        ])->assertRedirect(route('management.customers.index'));

        $this->assertDatabaseHas('customer_profiles', [
            'id' => $customer->id,
            'last_name' => 'Customer',
            'email' => 'walkin.updated@example.test',
        ]);

        $this->patch(route('management.customers.toggle-status', $customer));
        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_management_user_can_create_update_and_toggle_therapist_availability(): void
    {
        $manager = $this->createUserWithRole('management');
        $therapist = TherapistProfile::create([
            'first_name' => 'Schedule',
            'last_name' => 'Tester',
            'commission_rate' => 15,
            'status' => 'active',
        ]);

        $this->actingAs($manager)->post(route('management.availability.store'), [
            'therapist_profile_id' => $therapist->id,
            'availability_date' => null,
            'day_of_week' => 2,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'active',
            'notes' => 'Recurring Tuesday schedule.',
        ])->assertRedirect(route('management.availability.index'))
            ->assertSessionHas('success');

        $availability = TherapistAvailability::where('therapist_profile_id', $therapist->id)->firstOrFail();
        $this->assertSame(2, $availability->day_of_week);

        $this->put(route('management.availability.update', $availability), [
            'therapist_profile_id' => $therapist->id,
            'availability_date' => '2026-07-15',
            'day_of_week' => null,
            'start_time' => '10:00',
            'end_time' => '18:00',
            'status' => 'active',
            'notes' => 'Specific date schedule.',
        ])->assertRedirect(route('management.availability.index'));

        $availability->refresh();
        $this->assertSame('2026-07-15', $availability->availability_date->format('Y-m-d'));
        $this->assertNull($availability->day_of_week);

        $this->patch(route('management.availability.toggle-status', $availability));
        $this->assertSame('inactive', $availability->fresh()->status);
    }

    public function test_management_forms_reject_missing_or_invalid_data(): void
    {
        $manager = $this->createUserWithRole('management');
        $therapist = TherapistProfile::create([
            'first_name' => 'Validation',
            'commission_rate' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($manager)->post(route('management.services.store'), [])
            ->assertSessionHasErrors(['name', 'duration_minutes', 'price', 'status']);

        $this->post(route('management.therapists.store'), [
            'email' => 'invalid-email',
            'commission_rate' => 101,
        ])->assertSessionHasErrors(['first_name', 'email', 'commission_rate', 'status']);

        $this->post(route('management.customers.store'), [
            'birth_date' => now()->addDay()->format('Y-m-d'),
        ])->assertSessionHasErrors(['first_name', 'birth_date', 'is_active']);

        $this->post(route('management.availability.store'), [
            'therapist_profile_id' => $therapist->id,
            'availability_date' => '2026-07-15',
            'day_of_week' => 3,
            'start_time' => '17:00',
            'end_time' => '09:00',
            'status' => 'active',
        ])->assertSessionHasErrors(['availability_date', 'day_of_week', 'end_time']);
    }

    /**
     * @return list<string>
     */
    private function moduleIndexRoutes(): array
    {
        return [
            route('management.services.index'),
            route('management.therapists.index'),
            route('management.customers.index'),
            route('management.availability.index'),
        ];
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
