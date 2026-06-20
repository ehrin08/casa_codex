# Casa Paraiso Spa Management System

Casa Paraiso Spa Management System is a web-based service management and appointment booking system for Casa Paraiso - Body and Wellness Spa.

This repository contains the Sprint 1 foundation, Sprint 2 authentication and management modules, the Sprint 3 appointment workflow, and the Sprint 4 management-side cash transaction recording workflow. Later tasks will add commissions, promotions, analytics, reports, and reviews.

## UI Branding and Design

The interface uses a modern wellness spa design system based on Casa Paraiso's identity: deep cocoa brown, sage green, warm cream, and muted gold. The responsive shared layout includes text branding, a leaf mark built with inline SVG, role-aware desktop and mobile navigation, consistent page spacing, and a branded footer. Reusable Blade components provide cards, buttons, status badges, alerts, empty states, page headers, and accessible form controls.

No remote fonts, images, or other external visual assets are required. See [`docs/ui-branding.md`](docs/ui-branding.md) for design tokens, component usage, responsive behavior, and accessibility notes.

## Tech Stack

- Laravel
- Laravel Livewire
- Tailwind CSS
- MySQL
- Vite
- PHP
- Composer
- npm

## Local Setup

1. Install PHP 8.2 or newer, Composer, Node.js, npm, and MySQL.
2. Clone the repository.
3. Install PHP dependencies:

```bash
composer install
```

4. Install frontend dependencies:

```bash
npm install
```

5. Copy the environment file if needed:

```bash
cp .env.example .env
```

6. Generate an application key if needed:

```bash
php artisan key:generate
```

7. Build frontend assets:

```bash
npm run build
```

8. Run the local development servers:

```bash
php artisan serve
npm run dev
```

The app will be available from the Laravel local server, usually `http://127.0.0.1:8000`.

## Database Setup

1. Create a MySQL database for the project, for example:

```sql
CREATE DATABASE casa_paraiso_spa;
```

2. Update `.env` with the actual local MySQL credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=casa_paraiso_spa
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
```

3. Run migrations after credentials are configured:

```bash
php artisan migrate
```

To rebuild the local development database with seeded test data:

```bash
php artisan migrate:fresh --seed
```

Seeded users are development/test records only. All seeded test users use the password `password`.

## Authentication

The app uses a controller-based Laravel session authentication flow without an additional starter-kit package.

- `GET /login` displays the login form to guests.
- `POST /login` validates credentials, authenticates the user, and regenerates the session.
- `GET /dashboard` requires authentication and redirects to the user's role area.
- `POST /logout` requires authentication, logs out the user, invalidates the session, and regenerates the CSRF token.

Laravel's `web` middleware provides cookie-backed sessions and CSRF protection. Role middleware verifies the authenticated user's assigned role and returns `403 Forbidden` for cross-role access.

### Role-Based Access

| Protected route | Required role |
| --- | --- |
| `/management` | Management |
| `/management/appointments` | Management |
| `/therapist` | Therapist |
| `/therapist/schedule` | Therapist |
| `/customer` | Customer |
| `/customer/book-appointment` | Customer |
| `/customer/appointments` | Customer |
| `/notifications` | Any authenticated user; own records only |

Guests are redirected to `/login`. After login, `/dashboard` sends each user to their assigned role area. Authenticated navigation displays only that role area's link and the logout action. See `docs/rbac.md` for the implementation structure.

### Development Login Accounts

| Role | Email | Password |
| --- | --- | --- |
| Management | `management@example.test` | `password` |
| Therapist | `maya.therapist@example.test` | `password` |
| Therapist | `leo.therapist@example.test` | `password` |
| Customer | `ana.customer@example.test` | `password` |
| Customer | `miguel.customer@example.test` | `password` |

These are fake local development accounts. Do not use the shared test password in production.

## Management Modules

Management users can maintain appointment status and four core record types from `/management`:

| Module | Base route | Supported actions |
| --- | --- | --- |
| Appointments | `/management/appointments` | List, view, update status, inspect status history |
| Services | `/management/services` | List, create, edit, deactivate, reactivate |
| Therapist profiles | `/management/therapists` | List, create, edit, deactivate, reactivate |
| Customer profiles | `/management/customers` | List, create, edit, deactivate, reactivate |
| Therapist availability | `/management/availability` | List, create, edit, deactivate, reactivate |
| Cash transactions | `/management/transactions` | List, select completed appointment, record cash payment, view receipt |

These controller-based modules use dedicated Form Requests for validation. Therapist and customer profiles may link only to an unused user account with the matching role. Customer profiles may also remain unlinked for walk-in records. Availability records use either a recurring weekday or a specific date and require an end time later than the start time.

Records are not deleted by these modules. Existing `status` or `is_active` fields are toggled so historical records and future relationships remain intact. All routes use both `auth` and `role:management` middleware. See `docs/sprint-2-management-modules.md` for details.

## Application Routes

- `/` - public landing page
- `/login` - guest login page
- `/dashboard` - authenticated role redirect
- `/management` - management-only module hub
- `/management/appointments` - management appointment list
- `/management/appointments/{appointment}` - appointment detail and status history
- `PATCH /management/appointments/{appointment}/status` - management-only status update
- `/management/services` - management service records
- `/management/therapists` - management therapist profiles
- `/management/customers` - management customer profiles
- `/management/availability` - management therapist availability
- `GET /management/transactions` - management cash transaction list
- `GET /management/transactions/create` - select an eligible completed appointment and enter payment details
- `POST /management/transactions` - validate and record a cash transaction
- `GET /management/transactions/{transaction}` - receipt-style transaction detail
- `/therapist` - therapist-only area
- `/therapist/schedule` - assigned appointments for today and upcoming dates
- `/therapist/appointments/{appointment}` - therapist-owned appointment detail
- `/customer` - customer-only area
- `GET /customer/appointments` - customer-owned upcoming and past appointment list
- `GET /customer/book-appointment` - customer-only appointment booking form
- `POST /customer/book-appointment` - validate and create a pending customer appointment
- `GET /customer/appointments/{appointment}` - customer-owned appointment confirmation
- `GET /notifications` - authenticated user's notification list
- `PATCH /notifications/{notification}/read` - mark an owned notification as read

## Customer Appointment Booking

Authenticated customers can book an appointment from `/customer/book-appointment`. The form lists active services and active therapists and accepts an appointment date, start time, and optional notes. Dates in the past, inactive records, unavailable times, overlapping bookings, and invalid input are rejected.

The scheduler links the authenticated user's active customer profile, selected service, and selected therapist. It calculates the end time from the service duration and stores the service name, duration, and price as snapshots with a default `pending` status. No transaction or payment record is created.

### Scheduling Rules

The full appointment range must fit inside an active therapist availability window. Availability may target the exact appointment date or recur by weekday. Inactive availability does not permit booking, and appointments cannot span beyond the availability end time or cross midnight.

Bookings use half-open time ranges: an overlap exists when the new start is before an existing end and the new end is after an existing start. This rejects partial, containing, and exact overlaps while allowing adjacent appointments. Existing `pending`, `confirmed`, and `completed` appointments block the slot; `cancelled` and `no_show` appointments do not.

Bookings lock the selected therapist while availability and conflicts are checked and the appointment is inserted. This serializes concurrent booking attempts for the same therapist.

### Appointment Status Tracking

Management users can set an appointment to `pending`, `confirmed`, `completed`, `cancelled`, or `no_show`. Each actual status change records the previous status, new status, management user, optional notes, and change time in `appointment_status_histories`. Submitting the current status again does not create duplicate history. Customers, therapists, and guests cannot access the management status workflow.

### Appointment Views

Management users can view every appointment and filter by date, status, therapist, and customer. Therapist users see only appointments assigned to their linked therapist profile, grouped into today's schedule and upcoming work. Customer users see only appointments linked to their customer profile, separated into upcoming and past lists. Therapist and customer appointment details return `404 Not Found` when the appointment belongs to another profile.

### In-System Notifications

Creating a booking records an unread notification for every management user and for the assigned therapist when that profile has a linked user. An actual status change records an unread notification for the linked customer and assigned therapist. Repeating the current status creates neither history nor another notification.

Notifications are synchronous database records in the existing `notifications` table. Each record stores the recipient, title, message, event type, unread state, read timestamp, and appointment reference data. Authenticated users can list and mark only their own notifications as read. Email delivery is intentionally deferred; no SMTP credentials, external delivery, queue worker, or real-time broadcasting are required.

## Cash Transaction Recording

Management users can record one over-the-counter cash transaction for each completed appointment. The workflow starts from the Transactions area or a completed appointment detail page. Pending, confirmed, cancelled, and no-show appointments are not eligible, and appointments with an existing transaction are excluded.

The transaction subtotal uses the appointment's stored service price snapshot. If that snapshot is unavailable, the related service price is used. Management may enter a discount between zero and the subtotal; the server computes the final total as subtotal minus discount. Paid cash transactions require enough cash tendered to cover the total and automatically calculate change. Pending and void statuses do not store tendered cash or change.

Appointment eligibility, duplicate prevention, and all monetary calculations are rechecked inside a database transaction that locks the appointment row. Recording a cash transaction does not create therapist commission records; commission computation remains part of CPSMS-41. No online payment provider or external gateway is involved. See `docs/sprint-4-transactions.md` for implementation details.

## Database Structure

The initial Sprint 1 schema keeps Laravel's default `users`, `password_reset_tokens`, `sessions`, cache, and queue tables, then adds the core Casa Paraiso domain tables:

- `roles` for management, therapist, and customer access labels.
- `customer_profiles` and `therapist_profiles` for customer/staff records.
- `service_categories` and `services` for spa service setup.
- `therapist_availabilities` and `appointments` for scheduling.
- `transactions` and `therapist_commissions` for cash recording and commission reporting.
- `promotions`, `customer_rfm_scores`, and `promotion_usages` for future RFM-based promotions.
- `customer_reviews` for ratings and sentiment labels.
- `notifications` for in-system user notifications.
- `appointment_status_histories` for appointment audit trails.

See `docs/database-design.md` for table relationships and seed data notes.

## Development Seed Data

The current seeders add safe fake/test data only:

- Roles: management, therapist, customer.
- Sample service categories and spa services.
- Test users for management, therapist, and customer roles.
- Sample therapist profiles linked to therapist users.
- Sample customer profiles, including one walk-in style record without a linked user.
- Recurring weekly therapist availability records.

No private credentials or real customer/staff records are seeded.

## Sprint 1 Checklist

- [x] Laravel project foundation created.
- [x] Laravel Livewire installed.
- [x] Tailwind CSS installed and configured through Vite.
- [x] Vite configured for Laravel assets.
- [x] MySQL environment placeholders configured.
- [x] Initial Livewire folders created for Management, Therapist, Customer, and Auth areas.
- [x] Initial Blade view folders created for layouts, management, therapist, and customer areas.
- [x] Project README documented for local setup and database setup.
- [x] Initial database schema, models, and development seeders added.
- [x] Shared Blade layout created.
- [x] Placeholder routes created for public, management, therapist, and customer areas.
- [x] Safe development users, profiles, and therapist availability seed data added.

See `docs/sprint-1-foundation.md` for a concise Sprint 1 foundation summary.
