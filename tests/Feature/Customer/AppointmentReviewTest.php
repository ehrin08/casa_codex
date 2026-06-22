<?php

namespace Tests\Feature\Customer;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\CustomerReview;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_access_review_form_for_own_completed_appointment(): void
    {
        [$user, $customer] = $this->createCustomer();
        $appointment = $this->createAppointment($customer, Appointment::STATUS_COMPLETED);

        $this->actingAs($user)
            ->get(route('customer.appointments.review.create', $appointment))
            ->assertOk()
            ->assertSee('Write a Review')
            ->assertSee($appointment->service_name_snapshot);
    }

    public function test_customer_cannot_review_an_ineligible_appointment_status(): void
    {
        [$user, $customer] = $this->createCustomer();

        foreach ([
            Appointment::STATUS_PENDING,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ] as $status) {
            $appointment = $this->createAppointment($customer, $status);

            $this->actingAs($user)
                ->get(route('customer.appointments.review.create', $appointment))
                ->assertForbidden();

            $this->post(route('customer.appointments.review.store', $appointment), [
                'rating' => 5,
                'comment' => 'Excellent service.',
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('customer_reviews', 0);
    }

    public function test_customer_cannot_review_another_customers_appointment(): void
    {
        [$user] = $this->createCustomer('Review Owner');
        [, $otherCustomer] = $this->createCustomer('Appointment Owner');
        $appointment = $this->createAppointment($otherCustomer, Appointment::STATUS_COMPLETED);

        $this->actingAs($user)
            ->get(route('customer.appointments.review.create', $appointment))
            ->assertForbidden();

        $this->post(route('customer.appointments.review.store', $appointment), [
            'rating' => 4,
            'comment' => 'Great service.',
        ])->assertForbidden();
    }

    public function test_customer_cannot_review_the_same_appointment_twice(): void
    {
        [$user, $customer] = $this->createCustomer();
        $appointment = $this->createAppointment($customer, Appointment::STATUS_COMPLETED);
        $this->createReview($appointment, $customer);

        $this->actingAs($user)
            ->get(route('customer.appointments.review.create', $appointment))
            ->assertForbidden();

        $this->post(route('customer.appointments.review.store', $appointment), [
            'rating' => 1,
            'comment' => 'A duplicate review.',
        ])->assertForbidden();

        $this->assertDatabaseCount('customer_reviews', 1);
    }

    public function test_inactive_customer_cannot_review_a_completed_appointment(): void
    {
        [$user, $customer] = $this->createCustomer();
        $customer->update(['is_active' => false]);
        $appointment = $this->createAppointment($customer, Appointment::STATUS_COMPLETED);

        $this->actingAs($user)
            ->get(route('customer.appointments.review.create', $appointment))
            ->assertForbidden();
    }

    public function test_customer_can_submit_a_valid_review_with_all_required_links_and_sentiment(): void
    {
        $this->travelTo(Carbon::parse('2026-06-22 14:30:00'));

        try {
            [$user, $customer] = $this->createCustomer();
            $appointment = $this->createAppointment($customer, Appointment::STATUS_COMPLETED);

            $this->actingAs($user)
                ->post(route('customer.appointments.review.store', $appointment), [
                    'rating' => 5,
                    'comment' => 'The therapist was friendly and the service was excellent.',
                ])
                ->assertRedirect(route('customer.appointments.show', $appointment))
                ->assertSessionHas('success');

            $this->assertDatabaseHas('customer_reviews', [
                'customer_profile_id' => $customer->id,
                'appointment_id' => $appointment->id,
                'service_id' => $appointment->service_id,
                'rating' => 5,
                'comment' => 'The therapist was friendly and the service was excellent.',
                'sentiment_label' => CustomerReview::SENTIMENT_POSITIVE,
                'reviewed_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            $this->travelBack();
        }
    }

    public function test_review_submission_validates_rating_and_comment(): void
    {
        [$user, $customer] = $this->createCustomer();
        $appointment = $this->createAppointment($customer, Appointment::STATUS_COMPLETED);

        $this->actingAs($user)
            ->post(route('customer.appointments.review.store', $appointment), [
                'rating' => 6,
                'comment' => str_repeat('a', 2001),
            ])
            ->assertSessionHasErrors(['rating', 'comment']);
    }

    public function test_appointment_detail_only_shows_review_action_when_eligible(): void
    {
        [$user, $customer] = $this->createCustomer();
        $completed = $this->createAppointment($customer, Appointment::STATUS_COMPLETED);
        $pending = $this->createAppointment($customer, Appointment::STATUS_PENDING);

        $this->actingAs($user)
            ->get(route('customer.appointments.show', $completed))
            ->assertOk()
            ->assertSee('Write review');

        $this->get(route('customer.appointments.show', $pending))
            ->assertOk()
            ->assertDontSee('Write review');
    }

    public function test_appointment_detail_shows_existing_review_instead_of_action(): void
    {
        [$user, $customer] = $this->createCustomer();
        $appointment = $this->createAppointment($customer, Appointment::STATUS_COMPLETED);
        $review = $this->createReview($appointment, $customer, 'A relaxing and comfortable visit.');

        $this->actingAs($user)
            ->get(route('customer.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Your review')
            ->assertSee($review->comment)
            ->assertSee($review->sentiment_label)
            ->assertDontSee('Write review');
    }

    /** @return array{User, CustomerProfile} */
    private function createCustomer(string $name = 'Review Customer'): array
    {
        $role = Role::firstOrCreate(['name' => 'customer'], ['display_name' => 'Customer']);
        $user = User::factory()->create(['role_id' => $role->id, 'name' => $name]);
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, '');
        $customer = CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
        ]);

        return [$user, $customer];
    }

    private function createAppointment(CustomerProfile $customer, string $status): Appointment
    {
        $service = Service::create([
            'name' => 'Review Service '.Service::count(),
            'duration_minutes' => 60,
            'price' => 850,
            'status' => 'active',
        ]);
        $therapist = TherapistProfile::create([
            'first_name' => 'Review',
            'last_name' => 'Therapist',
            'commission_rate' => 20,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => today()->subDay(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => $status,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => 850,
        ]);
    }

    private function createReview(
        Appointment $appointment,
        CustomerProfile $customer,
        string $comment = 'Existing review comment.',
    ): CustomerReview {
        return CustomerReview::create([
            'customer_profile_id' => $customer->id,
            'appointment_id' => $appointment->id,
            'service_id' => $appointment->service_id,
            'rating' => 4,
            'comment' => $comment,
            'sentiment_label' => CustomerReview::SENTIMENT_POSITIVE,
            'reviewed_at' => now(),
        ]);
    }
}
