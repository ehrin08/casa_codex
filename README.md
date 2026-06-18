# Casa Paraiso Spa Management System

Casa Paraiso Spa Management System is a web-based service management and appointment booking system for Casa Paraiso - Body and Wellness Spa.

This repository currently contains the Sprint 1 foundation only. Business modules such as authentication, dashboards, booking, transactions, promotions, analytics, reports, reviews, and notifications will be added in later tasks.

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

## Placeholder Routes

- `/` - public landing page
- `/management` - management area placeholder
- `/therapist` - therapist area placeholder
- `/customer` - customer area placeholder

## Database Structure

The initial Sprint 1 schema keeps Laravel's default `users`, `password_reset_tokens`, `sessions`, cache, and queue tables, then adds the core Casa Paraiso domain tables:

- `roles` for management, therapist, and customer access labels.
- `customer_profiles` and `therapist_profiles` for customer/staff records.
- `service_categories` and `services` for spa service setup.
- `therapist_availabilities` and `appointments` for future scheduling.
- `transactions` and `therapist_commissions` for cash recording and commission reporting.
- `promotions`, `customer_rfm_scores`, and `promotion_usages` for future RFM-based promotions.
- `customer_reviews` for ratings and sentiment labels.
- `notifications` for in-system user notifications.
- `appointment_status_histories` for future appointment audit trails.

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
