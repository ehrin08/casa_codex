<?php

namespace Tests\Feature\Management;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ManagementDashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_user_can_view_dashboard(): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)
            ->get(route('management.index'))
            ->assertOk();
    }

    public function test_dashboard_displays_business_summary_kpi_labels(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Today’s Appointments');
        $response->assertSee('Today’s Paid Revenue');
        $response->assertSee('Pending Payments');
        $response->assertSee('Therapist Workload');
    }

    public function test_dashboard_shows_primary_staff_actions(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Book Walk-in');
        $response->assertSee('Today’s Appointments');
        $response->assertSee('Record Payment');
        $response->assertSee('Print Reports');
    }

    public function test_dashboard_displays_attention_needed_section(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Attention Needed');
    }

    public function test_dashboard_displays_business_insights_section(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Business Insights');
    }

    public function test_dashboard_displays_secondary_management_links(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Manage Records');
        $response->assertSee('Services');
        $response->assertSee('Therapists');
        $response->assertSee('Customers');
        $response->assertSee('Therapist Workload');
        $response->assertSee('Promotions');
        $response->assertSee('RFM Scores');
        $response->assertSee('Reviews & Sentiment');
        $response->assertSee('Analytics');
        $response->assertSee('Reports');
        $response->assertSee('Commissions');
    }

    public function test_customer_user_cannot_access_management_dashboard(): void
    {
        $customer = $this->createUserWithRole('customer');

        $this->actingAs($customer)
            ->get('/management')
            ->assertForbidden();
    }

    public function test_therapist_user_cannot_access_management_dashboard(): void
    {
        $therapist = $this->createUserWithRole('therapist');

        $this->actingAs($therapist)
            ->get('/management')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_management_dashboard(): void
    {
        $this->get('/management')
            ->assertRedirect(route('login'));
    }

    public function test_all_dashboard_route_names_resolve(): void
    {
        $requiredRoutes = [
            'management.index',
            'management.walk-ins.create',
            'management.appointments.index',
            'management.availability.index',
            'management.transactions.index',
            'management.commissions.index',
            'management.reports.index',
            'management.analytics.index',
            'management.rfm.index',
            'management.reviews.index',
            'management.promotions.index',
            'management.services.index',
            'management.therapists.index',
            'management.customers.index',
        ];

        foreach ($requiredRoutes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Route [{$routeName}] is referenced in the dashboard but does not exist."
            );
        }
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
