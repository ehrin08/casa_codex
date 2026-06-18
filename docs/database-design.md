# Database Design

This document summarizes the initial database structure for the Casa Paraiso Spa Management System. The schema is intentionally foundational: it supports future authentication, booking, cashier, commission, promotion, analytics, review, and notification modules without implementing those workflows yet.

## Core Access Tables

- `roles`
  - Stores the seeded role labels: `management`, `therapist`, and `customer`.
  - `users.role_id` links Laravel users to a role while preserving the default authentication-compatible users table.

- `users`
  - Laravel default users table is kept.
  - A nullable `role_id` foreign key is added for future role-based access control.

## Profile Tables

- `customer_profiles`
  - Stores customer details.
  - `user_id` is nullable so the system can support future walk-in records or customer records that are not yet tied to an authenticated account.

- `therapist_profiles`
  - Stores therapist/staff details.
  - Includes `commission_rate` and `status` fields for future commission computation and staff availability control.

## Services And Scheduling

- `service_categories`
  - Optional grouping table for spa services.

- `services`
  - Stores service name, description, duration, price, and status.
  - Appointments can snapshot service details so historical bookings remain stable if service prices change later.

- `therapist_availabilities`
  - Stores reusable weekday availability or date-specific availability windows.
  - Future booking logic can use `availability_date`, `day_of_week`, `start_time`, `end_time`, and `status`.

- `appointments`
  - Connects customers, therapists, and services.
  - Tracks appointment date, start/end time, status, notes, cancellation reason, and service snapshot fields.

- `appointment_status_histories`
  - Optional audit trail for future booking status transitions.

## Cash And Commission Tables

- `transactions`
  - Stores cash transaction records.
  - Can reference an appointment, customer profile, and cashier/management user.
  - Includes subtotal, discount, total amount, amount tendered, change due, payment method, payment status, and transaction date.

- `therapist_commissions`
  - Stores therapist commission records derived from appointments or transactions.
  - Includes commission rate, commission amount, status, and optional paid date.

## Promotion And Analytics Tables

- `promotions`
  - Stores promotion details and future rule fields for RFM or segment-based targeting.
  - Includes discount type/value, active date range, status, segment label, minimum RFM score fields, and a JSON rule payload.

- `customer_rfm_scores`
  - Stores periodic recency, frequency, and monetary scores for customers.
  - Supports future segmentation and promotion eligibility.

- `promotion_usages`
  - Connects applied promotions to transactions and customers for future promotion reporting.

## Review And Notification Tables

- `customer_reviews`
  - Stores customer ratings, comments, and a simple sentiment label.
  - Can reference customer, appointment, and service records.

- `notifications`
  - Stores in-system notification messages for users.
  - Uses a `SystemNotification` model to avoid confusion with Laravel's notification classes.

## Seed Data

Development/test seeders currently add:

- Roles: Management, Therapist, Customer.
- Service categories: Massage Therapy, Body Treatments, Wellness Packages.
- Sample services: Swedish Massage, Deep Tissue Massage, Ventosa Massage, Body Scrub, Relaxation Package.

No real private credentials are seeded.
