<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistCommission;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPrintViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_report_is_management_only(): void
    {
        $this->get(route('management.reports.print'))->assertRedirect('/login');

        foreach (['customer', 'therapist'] as $role) {
            $this->actingAs($this->user($role))->get(route('management.reports.print'))->assertForbidden();
        }

        $this->actingAs($this->user('management'))->get(route('management.reports.print'))
            ->assertOk()->assertSee('Sales and Commission Financial Report');
    }

    public function test_print_report_respects_daily_weekly_and_custom_filters(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 12:00'));

        try {
            $manager = $this->user('management');
            $therapist = $this->therapist('Filter Therapist');
            $this->transaction($this->appointment($therapist, 'Today Service'), Carbon::parse('2026-07-15 10:00'));
            $this->transaction($this->appointment($therapist, 'Monday Service'), Carbon::parse('2026-07-13 10:00'));
            $this->transaction($this->appointment($therapist, 'Custom Service'), Carbon::parse('2026-06-10 10:00'));

            $this->actingAs($manager)->get(route('management.reports.print'))
                ->assertSee('Today Service')->assertDontSee('Monday Service');
            $this->get(route('management.reports.print', ['period' => 'this_week']))
                ->assertSee('Today Service')->assertSee('Monday Service')->assertDontSee('Custom Service');
            $this->get(route('management.reports.print', ['period' => 'custom', 'date_from' => '2026-06-01', 'date_to' => '2026-06-15']))
                ->assertSee('Custom Service')->assertDontSee('Today Service')->assertSee('Jun 1, 2026 to Jun 15, 2026');
        } finally {
            $this->travelBack();
        }
    }

    public function test_print_report_shows_totals_summaries_and_detailed_records(): void
    {
        $manager = $this->user('management');
        $therapist = $this->therapist('Print Therapist');
        $appointment = $this->appointment($therapist, 'Evidence Massage');
        $transaction = $this->transaction($appointment, now(), Transaction::STATUS_PAID, 1000, 100, 900);
        TherapistCommission::create([
            'therapist_profile_id' => $therapist->id, 'transaction_id' => $transaction->id,
            'appointment_id' => $appointment->id, 'commission_rate' => 20,
            'commission_base_amount' => 1000, 'commission_amount' => 200,
            'status' => TherapistCommission::STATUS_PENDING,
        ]);

        $this->actingAs($manager)->get(route('management.reports.print'))
            ->assertOk()
            ->assertSeeInOrder(['Sales Summary', 'PHP 1,000.00', 'PHP 100.00', 'PHP 900.00'])
            ->assertSeeInOrder(['Commission Summary', 'PHP 200.00'])
            ->assertSee('Service Performance')->assertSee('Evidence Massage')
            ->assertSee('Therapist Commission Summary')->assertSee('Print Therapist')
            ->assertSee('Detailed Sales Records')->assertSee('Detailed Commission Records')
            ->assertSee('#'.$transaction->id)->assertSee('#'.$appointment->id)
            ->assertSee('Report Customer');
    }

    public function test_print_report_has_clear_empty_states(): void
    {
        $this->actingAs($this->user('management'))->get(route('management.reports.print'))
            ->assertOk()
            ->assertSee('No paid service sales match this report period.')
            ->assertSee('No therapist commissions match this report period.')
            ->assertSee('No sales records match this report period.')
            ->assertSee('No commission records match this report period.');
    }

    public function test_reports_index_links_to_print_view_with_normalized_filters(): void
    {
        $filters = ['period' => 'custom', 'date_from' => '2026-06-01', 'date_to' => '2026-06-15'];
        $url = route('management.reports.print', $filters);

        $this->actingAs($this->user('management'))->get(route('management.reports.index', $filters))
            ->assertOk()->assertSee('Print / Save as PDF')->assertSee($url);
    }

    private function user(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function therapist(string $name): TherapistProfile
    {
        [$first, $last] = explode(' ', $name, 2);

        return TherapistProfile::create(['first_name' => $first, 'last_name' => $last, 'commission_rate' => 20, 'status' => 'active']);
    }

    private function appointment(TherapistProfile $therapist, string $serviceName): Appointment
    {
        $customer = CustomerProfile::create(['first_name' => 'Report', 'last_name' => 'Customer', 'is_active' => true]);
        $service = Service::create(['name' => $serviceName, 'duration_minutes' => 60, 'price' => 1000, 'status' => 'active']);

        return Appointment::create([
            'customer_profile_id' => $customer->id, 'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id, 'appointment_date' => today(), 'start_time' => '10:00',
            'end_time' => '11:00', 'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $serviceName, 'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => 1000,
        ]);
    }

    private function transaction(Appointment $appointment, Carbon $date, string $status = Transaction::STATUS_PAID, float $subtotal = 1000, float $discount = 0, float $total = 1000): Transaction
    {
        return Transaction::withoutEvents(fn () => Transaction::create([
            'appointment_id' => $appointment->id, 'customer_profile_id' => $appointment->customer_profile_id,
            'subtotal' => $subtotal, 'discount_amount' => $discount, 'total_amount' => $total,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH, 'payment_status' => $status,
            'transaction_date' => $date,
        ]));
    }
}
