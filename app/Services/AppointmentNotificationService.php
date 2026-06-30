<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SystemNotification;
use App\Models\User;

class AppointmentNotificationService
{
    public function appointmentBooked(Appointment $appointment): void
    {
        $appointment->loadMissing(['customerProfile', 'therapistProfile.user']);
        $customerName = $appointment->customer_display_name === 'Customer unavailable'
            ? 'A customer'
            : $appointment->customer_display_name;
        $schedule = $this->scheduleDescription($appointment);

        User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'management'))
            ->each(function (User $user) use ($appointment, $customerName, $schedule): void {
                $this->create(
                    $user,
                    $appointment,
                    'New appointment booking',
                    $customerName.' booked '.$appointment->service_name_snapshot.' for '.$schedule.'.',
                    'appointment_booked',
                );
            });

        $therapistUser = $appointment->therapistProfile?->user;

        if ($therapistUser) {
            $this->create(
                $therapistUser,
                $appointment,
                'New appointment assigned',
                $customerName.' was assigned to you for '.$appointment->service_name_snapshot.' on '.$schedule.'.',
                'appointment_booked',
            );
        }
    }

    public function appointmentStatusChanged(Appointment $appointment, string $previousStatus): void
    {
        $appointment->loadMissing(['customerProfile.user', 'therapistProfile.user']);
        $status = ucfirst(str_replace('_', ' ', $appointment->status));
        $previous = ucfirst(str_replace('_', ' ', $previousStatus));
        $schedule = $this->scheduleDescription($appointment);

        $customerUser = $appointment->customerProfile?->user;

        if ($customerUser) {
            $this->create(
                $customerUser,
                $appointment,
                'Appointment status updated',
                'Your '.$appointment->service_name_snapshot.' appointment on '.$schedule.' changed from '.$previous.' to '.$status.'.',
                'appointment_status_changed',
            );
        }

        $therapistUser = $appointment->therapistProfile?->user;

        if ($therapistUser) {
            $this->create(
                $therapistUser,
                $appointment,
                'Assigned appointment status updated',
                'Appointment #'.$appointment->id.' on '.$schedule.' changed from '.$previous.' to '.$status.'.',
                'appointment_status_changed',
            );
        }
    }

    private function create(
        User $recipient,
        Appointment $appointment,
        string $title,
        string $message,
        string $type,
    ): void {
        SystemNotification::create([
            'recipient_user_id' => $recipient->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
            'data' => [
                'appointment_id' => $appointment->id,
                'appointment_status' => $appointment->status,
            ],
        ]);
    }

    private function scheduleDescription(Appointment $appointment): string
    {
        return $appointment->appointment_date->format('M j, Y')
            .' at '.date('g:i A', strtotime($appointment->start_time));
    }
}
