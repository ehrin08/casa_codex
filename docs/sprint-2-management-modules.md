# Sprint 2 Management Modules

CPSMS-36 adds controller-based CRUD pages for services, therapist profiles, customer profiles, and therapist availability. Every route is inside the existing `auth` and `role:management` middleware group.

## Modules

### Services

Management can list, create, and edit services with an optional category, description, duration, price, and active/inactive status. The status toggle preserves the service record for future appointment relationships.

### Therapist Profiles

Management can maintain therapist identity, contact, specialty, commission rate, hire date, notes, and status. A profile can optionally link to one unused user account assigned the `therapist` role. Employee codes and linked user accounts remain unique.

### Customer Profiles

Management can maintain customer identity, contact, demographic, address, notes, and active status. A profile can link to one unused `customer` user account or remain unlinked as a walk-in record.

### Therapist Availability

Management can maintain active or inactive therapist availability windows. Each window uses exactly one schedule type:

- A weekday from Sunday through Saturday for recurring weekly availability.
- A specific calendar date for one-time availability.

Start and end times are required, and the end time must be later than the start time.

## Routes and State Changes

Each module provides index, create, store, edit, and update routes under `/management`. A separate `PATCH` toggle-status route deactivates or reactivates records. No delete routes are exposed.

The shared layout displays management-only links for all four modules, success feedback after writes, and validation feedback when a form is rejected.
