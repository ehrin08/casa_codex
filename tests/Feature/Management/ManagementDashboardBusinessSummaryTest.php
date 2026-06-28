<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementDashboardBusinessSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_calculates_business_summaries_correctly(): void
    {
        $manager = $this->createUserWithRole('management');
        $today = CarbonImmutable::today();

        // Setup test data
        $therapist1 = TherapistProfile::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'commission_rate' => 20,
            'status' => 'active',
        ]);

        $therapist2 = TherapistProfile::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'commission_rate' => 20,
            'status' => 'active',
        ]);

        $service = Service::create([
            'name' => 'Massage',
            'description' => 'A massage',
            'duration_minutes' => 60,
            'price' => 100,
            'status' => 'active',
        ]);

        // Today's appointments (2 total: 1 pending, 1 completed)
        $pendingAppointment = Appointment::create([
            'appointment_date' => $today,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Appointment::STATUS_PENDING,
            'therapist_profile_id' => $therapist1->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);

        $completedAppointment = Appointment::create([
            'appointment_date' => $today,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => Appointment::STATUS_COMPLETED,
            'therapist_profile_id' => $therapist2->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);

        // Yesterday's appointment (should be ignored in today's count)
        Appointment::create([
            'appointment_date' => $today->subDay(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);

        // Transactions
        // 1 Paid transaction for today
        Transaction::create([
            'appointment_id' => $completedAppointment->id,
            'subtotal' => 150.00,
            'total_amount' => 150.00,
            'amount_tendered' => 150.00,
            'change_due' => 0,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => Transaction::STATUS_PAID,
            'paid_at' => now(),
            'transaction_date' => today(),
        ]);

        // 1 Pending transaction
        $dummyCompletedAppt = Appointment::create([
            'appointment_date' => $today,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);

        Transaction::create([
            'appointment_id' => $dummyCompletedAppt->id,
            'subtotal' => 100.00,
            'total_amount' => 100.00,
            'amount_tendered' => 0,
            'change_due' => 0,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => Transaction::STATUS_PENDING,
            'transaction_date' => today(),
        ]);

        // 1 Completed appointment WITHOUT transaction (adds to pending payments count)
        Appointment::create([
            'appointment_date' => $today,
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);

        $response = $this->actingAs($manager)->get(route('management.index'));

        $response->assertOk();

        // Assert View Data
        $response->assertViewHas('todayAppointments', 4); // 2 initially + 1 dummy + 1 no-transaction
        $response->assertViewHas('todayPaidRevenue', 150.00);

        // Pending payments: 1 pending transaction + 1 today completed appt w/o tx + 1 yesterday completed appt w/o tx = 3
        $response->assertViewHas('pendingPayments', 3);

        // Therapist workload: therapist1 and therapist2
        $response->assertViewHas('therapistsWorking', 2);

        $attentionNeeded = $response->viewData('attentionNeeded');
        $this->assertIsArray($attentionNeeded);
        $this->assertNotEmpty($attentionNeeded);
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)]
        );

        $user = new User;
        $user->role_id = $role->id;
        $user->name = 'Test Manager';
        $user->email = 'test@example.com';
        $user->password = bcrypt('password');
        $user->save();

        return $user;
    }
}
