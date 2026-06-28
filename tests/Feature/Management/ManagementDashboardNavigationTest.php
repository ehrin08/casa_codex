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

    public function test_dashboard_displays_grouped_section_headings(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Daily Operations',
            'Payments & Reports',
            'Customer Insights',
            'Marketing & Promotions',
            'System Records',
        ]);
    }

    public function test_dashboard_shows_walk_in_booking_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Walk-in Booking');
        $response->assertSee('Book walk-in');
    }

    public function test_dashboard_shows_appointments_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Appointments');
        $response->assertSee('View appointments');
    }

    public function test_dashboard_shows_analytics_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Analytics');
        $response->assertSee('View analytics');
    }

    public function test_dashboard_shows_reviews_and_sentiment_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Reviews & Sentiment');
        $response->assertSee('View feedback');
    }

    public function test_dashboard_shows_transactions_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Transactions');
        $response->assertSee('View transactions');
    }

    public function test_dashboard_shows_reports_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Reports');
        $response->assertSee('View reports');
    }

    public function test_dashboard_shows_promotions_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('Promotions');
        $response->assertSee('Manage promotions');
    }

    public function test_dashboard_shows_rfm_scores_card(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee('RFM Scores');
        $response->assertSee('View RFM scores');
    }

    public function test_sidebar_contains_walk_in_booking_link(): void
    {
        $manager = $this->createUserWithRole('management');

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();
        $response->assertSee(route('management.walk-ins.create'));
        $response->assertSee('Walk-in Booking');
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
            'notifications.index',
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
