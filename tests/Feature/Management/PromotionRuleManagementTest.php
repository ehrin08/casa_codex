<?php

namespace Tests\Feature\Management;

use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionRuleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_user_can_access_promotion_rule_pages(): void
    {
        $manager = $this->createUserWithRole('management');
        $promotion = $this->createPromotion();

        $this->actingAs($manager)->get(route('management.promotions.index'))->assertOk();
        $this->get(route('management.promotions.create'))->assertOk();
        $this->get(route('management.promotions.edit', $promotion))->assertOk();
    }

    public function test_guest_is_redirected_from_promotion_rule_management(): void
    {
        $this->get(route('management.promotions.index'))->assertRedirect('/login');
    }

    public function test_customer_and_therapist_users_cannot_access_promotion_rule_management(): void
    {
        foreach (['customer', 'therapist'] as $role) {
            $user = $this->createUserWithRole($role);

            $this->actingAs($user)
                ->get(route('management.promotions.index'))
                ->assertForbidden();
        }
    }

    public function test_management_dashboard_links_to_promotion_rules(): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)
            ->get(route('management.index'))
            ->assertOk()
            ->assertSee('Promotions')
            ->assertSee('href="'.route('management.promotions.index').'"', false);
    }

    public function test_management_can_create_a_percentage_discount_promotion_rule(): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)
            ->post(route('management.promotions.store'), $this->validData([
                'title' => 'Champion Loyalty Reward',
                'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
                'discount_value' => 15.5,
                'rfm_segment_label' => 'Champion',
                'rule_min_recency_score' => 4,
                'starts_at' => '2026-06-21T09:00',
                'ends_at' => '2026-06-30T18:00',
                'status' => Promotion::STATUS_ACTIVE,
            ]))
            ->assertRedirect(route('management.promotions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('promotions', [
            'title' => 'Champion Loyalty Reward',
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 15.5,
            'rfm_segment_label' => 'Champion',
            'rule_min_recency_score' => 4,
            'status' => Promotion::STATUS_ACTIVE,
        ]);
    }

    public function test_management_can_create_a_fixed_discount_promotion_rule(): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)
            ->post(route('management.promotions.store'), $this->validData([
                'title' => 'Return Visit Credit',
                'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
                'discount_value' => 250,
                'rfm_segment_label' => null,
                'rule_min_frequency_score' => 3,
            ]))
            ->assertRedirect(route('management.promotions.index'));

        $this->assertDatabaseHas('promotions', [
            'title' => 'Return Visit Credit',
            'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
            'discount_value' => 250,
            'rule_min_frequency_score' => 3,
        ]);
    }

    public function test_percentage_discount_above_one_hundred_is_rejected(): void
    {
        $this->assertPromotionDataIsInvalid(
            ['discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE, 'discount_value' => 100.01],
            'discount_value',
        );
    }

    public function test_zero_and_negative_discount_values_are_rejected(): void
    {
        foreach ([0, -1] as $discountValue) {
            $this->assertPromotionDataIsInvalid(['discount_value' => $discountValue], 'discount_value');
        }
    }

    public function test_rfm_thresholds_outside_one_to_five_are_rejected(): void
    {
        foreach ([0, 6] as $threshold) {
            $this->assertPromotionDataIsInvalid(
                ['rule_min_recency_score' => $threshold],
                'rule_min_recency_score',
            );
        }
    }

    public function test_invalid_rfm_segment_is_rejected(): void
    {
        $this->assertPromotionDataIsInvalid(['rfm_segment_label' => 'Superfan'], 'rfm_segment_label');
    }

    public function test_at_least_one_targeting_condition_is_required(): void
    {
        $this->assertPromotionDataIsInvalid([
            'rfm_segment_label' => null,
            'rule_min_recency_score' => null,
            'rule_min_frequency_score' => null,
            'rule_min_monetary_score' => null,
        ], 'rfm_segment_label');
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $this->assertPromotionDataIsInvalid([
            'starts_at' => '2026-06-30T18:00',
            'ends_at' => '2026-06-21T09:00',
        ], 'ends_at');
    }

    public function test_management_can_edit_a_promotion_rule(): void
    {
        $manager = $this->createUserWithRole('management');
        $promotion = $this->createPromotion([
            'title' => 'Original Rule',
            'status' => Promotion::STATUS_DRAFT,
        ]);

        $this->actingAs($manager)
            ->put(route('management.promotions.update', $promotion), $this->validData([
                'title' => 'Updated Loyal Customer Rule',
                'description' => 'Updated eligibility and discount.',
                'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
                'discount_value' => 300,
                'rfm_segment_label' => 'Loyal Customer',
                'rule_min_recency_score' => 3,
                'status' => Promotion::STATUS_INACTIVE,
            ]))
            ->assertRedirect(route('management.promotions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'title' => 'Updated Loyal Customer Rule',
            'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
            'discount_value' => 300,
            'rfm_segment_label' => 'Loyal Customer',
            'rule_min_recency_score' => 3,
            'status' => Promotion::STATUS_INACTIVE,
        ]);
    }

    public function test_management_can_toggle_a_promotion_rule_between_active_and_inactive(): void
    {
        $manager = $this->createUserWithRole('management');
        $promotion = $this->createPromotion(['status' => Promotion::STATUS_ACTIVE]);

        $this->actingAs($manager)
            ->patch(route('management.promotions.toggle-status', $promotion))
            ->assertSessionHas('success');

        $this->assertSame(Promotion::STATUS_INACTIVE, $promotion->fresh()->status);

        $this->patch(route('management.promotions.toggle-status', $promotion));

        $this->assertSame(Promotion::STATUS_ACTIVE, $promotion->fresh()->status);
    }

    public function test_status_and_segment_filters_work_on_the_index_page(): void
    {
        $manager = $this->createUserWithRole('management');
        $this->createPromotion([
            'title' => 'Visible Champion Rule',
            'rfm_segment_label' => 'Champion',
            'status' => Promotion::STATUS_ACTIVE,
        ]);
        $this->createPromotion([
            'title' => 'Hidden Draft Champion Rule',
            'rfm_segment_label' => 'Champion',
            'status' => Promotion::STATUS_DRAFT,
        ]);
        $this->createPromotion([
            'title' => 'Hidden Active At Risk Rule',
            'rfm_segment_label' => 'At Risk',
            'status' => Promotion::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->get(route('management.promotions.index', [
                'status' => Promotion::STATUS_ACTIVE,
                'segment' => 'Champion',
            ]))
            ->assertOk()
            ->assertSee('Visible Champion Rule')
            ->assertDontSee('Hidden Draft Champion Rule')
            ->assertDontSee('Hidden Active At Risk Rule');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Seasonal RFM Rule',
            'description' => 'Configured for future promotion engine evaluation.',
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
            'rfm_segment_label' => 'Champion',
            'rule_min_recency_score' => null,
            'rule_min_frequency_score' => null,
            'rule_min_monetary_score' => null,
            'starts_at' => null,
            'ends_at' => null,
            'status' => Promotion::STATUS_DRAFT,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function assertPromotionDataIsInvalid(array $overrides, string $field): void
    {
        $manager = $this->createUserWithRole('management');

        $this->actingAs($manager)
            ->from(route('management.promotions.create'))
            ->post(route('management.promotions.store'), $this->validData($overrides))
            ->assertRedirect(route('management.promotions.create'))
            ->assertSessionHasErrors($field);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPromotion(array $overrides = []): Promotion
    {
        return Promotion::create($this->validData($overrides));
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }
}
