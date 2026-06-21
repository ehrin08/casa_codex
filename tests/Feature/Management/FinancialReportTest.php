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

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_management_only_and_linked_from_the_dashboard(): void
    {
        $this->get(route('management.reports.index'))->assertRedirect('/login');

        foreach (['customer', 'therapist'] as $roleName) {
            $this->actingAs($this->createUser($roleName))
                ->get(route('management.reports.index'))
                ->assertForbidden();
        }

        $manager = $this->createUser('management');
        $this->actingAs($manager)
            ->get(route('management.reports.index'))
            ->assertOk()
            ->assertSee('Sales and Commission Reports');

        $this->get(route('management.index'))
            ->assertOk()
            ->assertSee('href="'.route('management.reports.index').'"', false)
            ->assertSee('Review sales, service performance, and therapist commission summaries.');
    }

    public function test_daily_report_calculates_paid_sales_counts_and_service_performance(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 12:00:00'));

        try {
            $manager = $this->createUser('management');
            $therapist = $this->createTherapist('Daily Therapist');
            $paidAppointment = $this->createAppointment($therapist, 'Historical Hilot');
            $pendingAppointment = $this->createAppointment($therapist, 'Pending Service');
            $voidAppointment = $this->createAppointment($therapist, 'Void Service');
            $outsideAppointment = $this->createAppointment($therapist, 'Outside Service');

            $this->createTransaction($paidAppointment, now(), Transaction::STATUS_PAID, 1000, 100, 900);
            $this->createTransaction($pendingAppointment, now(), Transaction::STATUS_PENDING, 500, 0, 500);
            $this->createTransaction($voidAppointment, now(), Transaction::STATUS_VOID, 700, 50, 650);
            $this->createTransaction($outsideAppointment, now()->subDay(), Transaction::STATUS_PAID, 2000, 0, 2000);

            $response = $this->actingAs($manager)->get(route('management.reports.index'));

            $response
                ->assertOk()
                ->assertSee('Historical Hilot')
                ->assertDontSee('Outside Service')
                ->assertViewHas('report', function (array $report): bool {
                    return $report['sales_summary'] === [
                        'gross_sales' => 1000.0,
                        'discounts' => 100.0,
                        'net_sales' => 900.0,
                        'paid_count' => 1,
                        'pending_count' => 1,
                        'void_count' => 1,
                    ]
                        && $report['transactions']->count() === 3
                        && $report['service_performance']->count() === 1
                        && $report['service_performance']->first()['service'] === 'Historical Hilot'
                        && $report['service_performance']->first()['average_sale'] === 900.0;
                });
        } finally {
            $this->travelBack();
        }
    }

    public function test_weekly_filter_includes_the_current_monday_through_sunday_only(): void
    {
        $this->travelTo(Carbon::parse('2026-07-15 12:00:00'));

        try {
            $manager = $this->createUser('management');
            $therapist = $this->createTherapist('Weekly Therapist');
            $monday = $this->createAppointment($therapist, 'Monday Service');
            $sunday = $this->createAppointment($therapist, 'Sunday Service');
            $outside = $this->createAppointment($therapist, 'Previous Sunday Service');

            $this->createTransaction($monday, Carbon::parse('2026-07-13 09:00'), Transaction::STATUS_PAID);
            $this->createTransaction($sunday, Carbon::parse('2026-07-19 17:00'), Transaction::STATUS_PAID);
            $this->createTransaction($outside, Carbon::parse('2026-07-12 17:00'), Transaction::STATUS_PAID);

            $this->actingAs($manager)
                ->get(route('management.reports.index', ['period' => 'this_week']))
                ->assertOk()
                ->assertSee('Monday Service')
                ->assertSee('Sunday Service')
                ->assertDontSee('Previous Sunday Service')
                ->assertViewHas('report', fn (array $report): bool => $report['transactions']->count() === 2);
        } finally {
            $this->travelBack();
        }
    }

    public function test_custom_date_range_filters_records_and_validates_range_order(): void
    {
        $manager = $this->createUser('management');
        $therapist = $this->createTherapist('Custom Therapist');
        $inside = $this->createAppointment($therapist, 'Inside Range Service');
        $outside = $this->createAppointment($therapist, 'Outside Range Service');
        $this->createTransaction($inside, Carbon::parse('2026-06-10 12:00'), Transaction::STATUS_PAID);
        $this->createTransaction($outside, Carbon::parse('2026-06-20 12:00'), Transaction::STATUS_PAID);

        $this->actingAs($manager)
            ->get(route('management.reports.index', [
                'period' => 'custom',
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-15',
            ]))
            ->assertOk()
            ->assertSee('Inside Range Service')
            ->assertDontSee('Outside Range Service')
            ->assertSee('value="2026-06-01"', false)
            ->assertSee('value="2026-06-15"', false);

        $this->get(route('management.reports.index', [
            'period' => 'custom',
            'date_from' => '2026-06-15',
            'date_to' => '2026-06-01',
        ]))->assertSessionHasErrors('date_to');
    }

    public function test_commission_totals_and_therapist_groups_use_transaction_dates(): void
    {
        $manager = $this->createUser('management');
        $firstTherapist = $this->createTherapist('First Therapist');
        $secondTherapist = $this->createTherapist('Second Therapist');
        $date = now();

        $firstTransaction = $this->createTransaction(
            $this->createAppointment($firstTherapist, 'First Service'),
            $date,
            Transaction::STATUS_PAID,
        );
        $secondTransaction = $this->createTransaction(
            $this->createAppointment($firstTherapist, 'Second Service'),
            $date,
            Transaction::STATUS_PAID,
        );
        $thirdTransaction = $this->createTransaction(
            $this->createAppointment($secondTherapist, 'Third Service'),
            $date,
            Transaction::STATUS_VOID,
        );

        $this->createCommission($firstTherapist, $firstTransaction, TherapistCommission::STATUS_PENDING, 100);
        $this->createCommission($firstTherapist, $secondTransaction, TherapistCommission::STATUS_PAID, 200);
        $this->createCommission($secondTherapist, $thirdTransaction, TherapistCommission::STATUS_VOID, 50);

        $this->actingAs($manager)
            ->get(route('management.reports.index'))
            ->assertOk()
            ->assertSee('First Therapist')
            ->assertSee('Second Therapist')
            ->assertViewHas('report', function (array $report): bool {
                $firstTherapist = $report['therapist_summary']->firstWhere('therapist', 'First Therapist');

                return $report['commission_summary'] === [
                    'total_generated' => 350.0,
                    'pending_total' => 100.0,
                    'paid_total' => 200.0,
                    'void_total' => 50.0,
                    'pending_count' => 1,
                    'paid_count' => 1,
                    'void_count' => 1,
                ]
                    && $firstTherapist['commission_count'] === 2
                    && $firstTherapist['pending_total'] === 100.0
                    && $firstTherapist['paid_total'] === 200.0
                    && $firstTherapist['total_generated'] === 300.0;
            });
    }

    public function test_empty_report_displays_a_clear_empty_state(): void
    {
        $this->actingAs($this->createUser('management'))
            ->get(route('management.reports.index'))
            ->assertOk()
            ->assertSee('No financial records found')
            ->assertSee('No sales or commission records match this report period.');
    }

    private function createUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createTherapist(string $name): TherapistProfile
    {
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);

        return TherapistProfile::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'commission_rate' => 20,
            'status' => 'active',
        ]);
    }

    private function createAppointment(TherapistProfile $therapist, string $serviceSnapshot): Appointment
    {
        $customer = CustomerProfile::create([
            'first_name' => 'Report',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $service = Service::create([
            'name' => $serviceSnapshot.' Current',
            'duration_minutes' => 60,
            'price' => 1000,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => today(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $serviceSnapshot,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => 1000,
        ]);
    }

    private function createTransaction(
        Appointment $appointment,
        Carbon $date,
        string $status,
        float $subtotal = 1000,
        float $discount = 0,
        float $total = 1000,
    ): Transaction {
        return Transaction::withoutEvents(fn (): Transaction => Transaction::create([
            'appointment_id' => $appointment->id,
            'customer_profile_id' => $appointment->customer_profile_id,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => $status,
            'transaction_date' => $date,
        ]));
    }

    private function createCommission(
        TherapistProfile $therapist,
        Transaction $transaction,
        string $status,
        float $amount,
    ): TherapistCommission {
        return TherapistCommission::create([
            'therapist_profile_id' => $therapist->id,
            'transaction_id' => $transaction->id,
            'appointment_id' => $transaction->appointment_id,
            'commission_rate' => 20,
            'commission_base_amount' => $transaction->subtotal,
            'commission_amount' => $amount,
            'status' => $status,
        ]);
    }
}
