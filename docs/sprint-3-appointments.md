# Sprint 3 - Appointment Booking and Scheduling

## CPSMS-37 Scope

Customers can open `/customer/book-appointment`, view active services, and submit a pending appointment request with an active therapist, date, start time, and optional notes. The booking flow uses a controller and Form Request under the existing `auth` and `role:customer` middleware.

On creation, the appointment is linked to the authenticated user's active customer profile. The selected service supplies the calculated end time and the service name, duration, and price snapshots. The confirmation route only displays appointments owned by the authenticated customer.

## Validation and Access

- Service and therapist selections are required and must reference active records.
- The appointment date must be today or later.
- The start time must use a valid 24-hour time value.
- Notes are optional and limited to 2,000 characters.
- Guests are redirected to login.
- Management and therapist users receive a forbidden response.
- Customers without an active linked profile cannot use the booking flow.

## CPSMS-38 Scheduling Rules

Appointment creation runs through `App\Services\AppointmentScheduler`. The selected service duration determines the end time. The scheduler locks the active therapist record inside the booking transaction, then validates availability and appointment conflicts before inserting the pending appointment.

An appointment must fit completely inside at least one active availability record for the selected therapist. A matching record can use the exact `availability_date` or a recurring `day_of_week`. Inactive availability and ranges that end outside the window are rejected. Overnight appointments are not supported by the current same-day availability schema.

Time ranges use the half-open overlap rule `new_start < existing_end AND new_end > existing_start`. Partial overlaps, exact matches, and new appointments that contain an existing appointment are rejected. Appointments that touch only at an endpoint are allowed. `pending`, `confirmed`, and `completed` appointments block a slot; `cancelled` and `no_show` appointments are ignored.

## Status Tracking

Management users can view appointment lists and details under `/management/appointments`. Allowed statuses are `pending`, `confirmed`, `completed`, `cancelled`, and `no_show`.

Each actual status change creates an `appointment_status_histories` record containing the appointment, previous and new status, authenticated management user, optional notes, and timestamp. The appointment row is locked during updates, and submitting an unchanged status does not create history.

Customer booking routes remain customer-only. Management appointment routes use the existing `auth` and `role:management` middleware; customers, therapists, and guests cannot update status.

## Deferred Work

Full therapist schedule views, notifications, payments, transactions, commissions, and later analytics remain outside CPSMS-38.
