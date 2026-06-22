<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\CustomerReview;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_can_view_review_index_summary_and_dashboard_link(): void
    {
        $manager = $this->createUser('management');
        [, $customer] = $this->createCustomer();
        $positive = $this->createReview($customer, 5, CustomerReview::SENTIMENT_POSITIVE, 'Excellent service.');
        $this->createReview($customer, 3, CustomerReview::SENTIMENT_NEUTRAL, 'It was acceptable.');
        $this->createReview($customer, 1, CustomerReview::SENTIMENT_NEGATIVE, 'Poor service.');

        $this->actingAs($manager)
            ->get(route('management.reviews.index'))
            ->assertOk()
            ->assertSee('Reviews &amp; Sentiment', false)
            ->assertSee($positive->comment)
            ->assertViewHas('summary', fn (array $summary): bool => $summary === [
                'total' => 3,
                'average_rating' => 3.0,
                'positive' => 1,
                'neutral' => 1,
                'negative' => 1,
            ]);

        $this->get(route('management.index'))
            ->assertOk()
            ->assertSee('href="'.route('management.reviews.index').'"', false)
            ->assertSee('Review customer feedback, ratings, and sentiment trends.');
    }

    public function test_management_can_view_review_detail(): void
    {
        $manager = $this->createUser('management');
        [, $customer] = $this->createCustomer('Detail Customer');
        $review = $this->createReview($customer, 4, CustomerReview::SENTIMENT_POSITIVE, 'A detailed review of the excellent service.');

        $this->actingAs($manager)
            ->get(route('management.reviews.show', $review))
            ->assertOk()
            ->assertSee($review->comment)
            ->assertSee('Detail Customer')
            ->assertSee($review->service->name)
            ->assertSee('4 / 5')
            ->assertSee('positive');
    }

    public function test_management_can_filter_reviews_by_sentiment_and_rating(): void
    {
        $manager = $this->createUser('management');
        [, $customer] = $this->createCustomer();
        $target = $this->createReview($customer, 1, CustomerReview::SENTIMENT_NEGATIVE, 'Target negative review.');
        $other = $this->createReview($customer, 5, CustomerReview::SENTIMENT_POSITIVE, 'Other positive review.');

        $this->actingAs($manager)
            ->get(route('management.reviews.index', ['sentiment' => 'negative', 'rating' => 1]))
            ->assertOk()
            ->assertSee($target->comment)
            ->assertDontSee($other->comment)
            ->assertViewHas('summary', fn (array $summary): bool => $summary['total'] === 1
                && $summary['negative'] === 1
                && $summary['average_rating'] === 1.0);
    }

    public function test_management_can_filter_reviews_by_service_and_date_range(): void
    {
        $manager = $this->createUser('management');
        [, $customer] = $this->createCustomer();
        $targetDate = Carbon::parse('2026-06-15 10:00:00');
        $target = $this->createReview($customer, 4, CustomerReview::SENTIMENT_POSITIVE, 'Target service and date.', $targetDate);
        $this->createReview($customer, 4, CustomerReview::SENTIMENT_POSITIVE, 'Wrong service.', $targetDate, $this->createService('Different Service'));
        $this->createReview($customer, 4, CustomerReview::SENTIMENT_POSITIVE, 'Wrong date.', $targetDate->copy()->subMonth(), $target->service);

        $this->actingAs($manager)
            ->get(route('management.reviews.index', [
                'service_id' => $target->service_id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee($target->comment)
            ->assertDontSee('Wrong service.')
            ->assertDontSee('Wrong date.')
            ->assertViewHas('reviews', fn ($reviews): bool => $reviews->total() === 1);
    }

    public function test_review_management_pages_are_role_protected(): void
    {
        [, $customer] = $this->createCustomer();
        $review = $this->createReview($customer, 5, CustomerReview::SENTIMENT_POSITIVE, 'Protected review.');

        $this->get(route('management.reviews.index'))->assertRedirect('/login');
        $this->get(route('management.reviews.show', $review))->assertRedirect('/login');

        foreach (['customer', 'therapist'] as $role) {
            $user = $role === 'customer' ? $customer->user : $this->createUser($role);

            $this->actingAs($user)->get(route('management.reviews.index'))->assertForbidden();
            $this->get(route('management.reviews.show', $review))->assertForbidden();
        }
    }

    private function createUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    /** @return array{User, CustomerProfile} */
    private function createCustomer(string $name = 'Insight Customer'): array
    {
        $user = $this->createUser('customer');
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, '');
        $customer = CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
        ]);

        return [$user, $customer];
    }

    private function createService(?string $name = null): Service
    {
        return Service::create([
            'name' => $name ?? 'Insight Service '.Service::count(),
            'duration_minutes' => 60,
            'price' => 900,
            'status' => 'active',
        ]);
    }

    private function createReview(
        CustomerProfile $customer,
        int $rating,
        string $sentiment,
        string $comment,
        ?Carbon $reviewedAt = null,
        ?Service $service = null,
    ): CustomerReview {
        $service ??= $this->createService();
        $appointment = Appointment::create([
            'customer_profile_id' => $customer->id,
            'service_id' => $service->id,
            'appointment_date' => ($reviewedAt ?? now())->copy()->subDay(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => 900,
        ]);

        return CustomerReview::create([
            'customer_profile_id' => $customer->id,
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'rating' => $rating,
            'comment' => $comment,
            'sentiment_label' => $sentiment,
            'reviewed_at' => $reviewedAt ?? now(),
        ]);
    }
}
