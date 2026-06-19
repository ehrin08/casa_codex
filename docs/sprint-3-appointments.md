# Sprint 3 - Customer Appointment Booking

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

## Deferred Work

Therapist availability matching, overlapping appointment prevention, appointment status management, payment creation, and staff scheduling remain outside CPSMS-37. These belong to CPSMS-38 and later tasks.
