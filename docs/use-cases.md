# People Analysis: Use Cases

This document outlines the actors and use cases for the Casa Paraiso Spa Management System, aligning with the SD1 requirements.

## 1. Actors

**Primary Actors:**
- **Customer:** A user (guest or registered) who interacts with the system to book and track their own spa appointments and view personal notifications.
- **Therapist:** A spa staff member who provides services. They interact with the system to view their assigned schedule and monitor their earned commissions.

**Secondary Actors:**
- **Management (Admin/Cashier):** Spa owners or administrative staff responsible for maintaining the system’s master records (services, profiles, availability), managing appointment lifecycles, recording cash transactions, and settling therapist commissions.

## 2. Major System Functions

### Customer Functions
- **Book Appointment:** Customers select an active service, choose an available therapist, and request a date and time.
- **View Appointments:** Customers view their upcoming and past appointments.
- **View Notifications:** Customers receive and review in-system notifications (e.g., appointment status changes).

### Therapist Functions
- **View Assigned Schedule:** Therapists view their appointments for today and upcoming dates.
- **View Own Commissions:** Therapists monitor their pending, paid, and void commission records.
- **View Notifications:** Therapists receive and review notifications regarding new bookings and status updates.

### Management Functions
- **Manage Spa Services:** Create, edit, and toggle the active status of spa services.
- **Manage Profiles & Availability:** Maintain therapist profiles, customer profiles, and configure recurring or date-specific therapist availability windows.
- **Manage Appointment Status:** View all appointments and update their statuses (pending, confirmed, completed, cancelled, no_show), keeping an audit history.
- **Record Cash Transactions:** Complete an over-the-counter cash payment for a completed appointment and generate a receipt.
- **Monitor & Settle Commissions:** Review all therapist commissions and mark pending commissions as paid.
- **View Notifications:** Receive system alerts for new customer bookings.

## 3. Use Case Diagram

The use case diagram is available as a generated image:
![Use Case Diagram](file:///c:/xampp/htdocs/casa_codex/docs/use-case-diagram.png)

*(The diagram was generated using Mermaid CLI from `docs/use-case-diagram.mmd`)*
