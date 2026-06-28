# 6. Key Design Decisions and Project Readiness

This document outlines the presentation slide content, Q&A defense preparation guide, and feasibility justifications for **Section 6: Key Design Decisions and Project Readiness** of the Casa Paraiso Spa Management System.

---

## Part A: Presentation Slide Structure

Here is the structured content designed for the **Key Design Decisions and Project Readiness** slides of the presentation.

### Slide 6: Key Design Decisions & Project Readiness
* **Title:** Key Design Decisions & Project Readiness
* **Subtitle:** Establishing a robust, reliable, and consistent operational foundation.
* **Key Architectural Decisions:**
  * **People & Process:**
    * **Nullable User Links:** Customer profiles decoupled from login accounts to support walk-in (unregistered) clients.
    * **Half-Open Scheduling:** Overlap check (`new_start < existing_end AND new_end > existing_start`) allows seamless back-to-back bookings.
    * **Soft Status Toggles:** Soft-deactivations preserve reference integrity for historical financial audits.
  * **Technology & Data:**
    * **Service Price Snapshots:** Immutable booking snapshots protect historical billing against future price changes.
    * **Concurrency DB Locks:** Row-level `lockForUpdate` in transactions prevents scheduling and payment double-booking races.
    * **Lean Security & RBAC:** Custom role middleware and session auth avoid dependency bloat and simplify access control.
* **Project Readiness & Verification:**
  * **Operational Workflows:** Core scheduler, transaction ledger, and therapist commission engine are fully implemented.
  * **Branded Frontend System:** Reusable, responsive Blade components using local assets and custom Tailwind spa tokens.
  * **Production-Grade Testing:** Comprehensive feature tests validate access controls, constraints, and calculations.

---

## Part B: Q&A Defense Guide (Justification of Design Decisions)

Use these detailed rationales to defend design choices during the Q&A panel.

### 1. People/User Analysis Justifications
* **Q: Why are customer profiles separate from user accounts?**
  * *A:* This separation supports walk-in customers who do not want to register a web account. It allows the spa to record contact details and compile treatment history without forcing the creation of login credentials, while still allowing a user account to be linked later.
* **Q: Why use custom role middleware instead of a package like Spatie Permission?**
  * *A:* The system uses a simple, rigid hierarchy: Management, Therapist, and Customer. A lightweight custom middleware (`EnsureUserHasRole`) and helper methods on the `User` model meet these needs with zero overhead, keeping the codebase simple and easy to audit.

### 2. Process Design Justifications
* **Q: Why use the half-open overlap rule for scheduling?**
  * *A:* Spa appointments typically run back-to-back. If a 1-hour service starts at 1:00 PM and ends at 2:00 PM, the next service can start exactly at 2:00 PM. Standard closed-interval checks (`<=` and `>=`) would reject this as an overlap. The half-open rule correctly permits adjacent slots.
* **Q: Why don't you delete records (e.g., services or profiles) when requested?**
  * *A:* Hard-deleting a service or therapist profile would break foreign key integrity or result in orphaned records in appointments, transactions, and commissions. Toggling `is_active` or `status` preserves historical business data for financial reports.

### 3. Technology Selection Justifications
* **Q: Why Laravel, Livewire, and Tailwind (TILT) instead of React or Vue?**
  * *A:* Livewire allows us to build a dynamic, SPA-like user experience (e.g., interactive calendars, reactive filters) entirely within PHP and Blade templates. This avoids the complexity of client-side routing, API state synchronization, and separate build targets, dramatically accelerating development and maintaining a simple deployable artifact.
* **Q: Why does the system use custom SVGs and avoid Google Fonts or external CDNs?**
  * *A:* Localizing all visual assets makes the application self-contained, offline-capable, and immune to third-party network outages or CDN latency.

### 4. Data Structure Design Justifications
* **Q: Why store service details as snapshots in the appointments table?**
  * *A:* If a customer books a Swedish Massage for $50, and the spa increases the price to $60 next month, the original booking must still reflect $50. Snapshotting the service name, duration, and price inside the `appointments` record at the time of booking isolates historical data from future service updates.
* **Q: Why database-level locks (`lockForUpdate`) instead of application-level checks?**
  * *A:* Under high concurrent traffic, two customers could click "Book" for the same therapist slot at the same millisecond. Application-level queries might both see the slot as free. `lockForUpdate` serializes database writes at the transaction level, forcing the second transaction to wait and fail the overlap check once the first completes.

---

## Part C: Project Readiness Assessment

| Component | Status | Verification Method | Readiness Notes |
|---|---|---|---|
| **Database Schema** | **Ready** | Migration runs & tests | Schema supports roles, profiles, availability, appointments, transactions, and commissions. |
| **Authentication & RBAC** | **Ready** | Middleware tests | Route-level role enforcement works; redirects based on user role are fully implemented. |
| **Scheduling Core** | **Ready** | `AppointmentScheduler` tests | Conflict checks, availability matching, and snapshot copying are functional and tested. |
| **Cash Transactions** | **Ready** | `TransactionRecording` tests | Validates appointment eligibility, calculates change due, and protects against duplicate payments. |
| **Therapist Commissions** | **Ready** | `TherapistCommission` tests | Automates calculations, snapshots rates, and enforces state changes (pending -> paid / void). |
| **UI Components** | **Ready** | Visual/Responsive checks | Custom spa design theme is fully integrated via reusable Blade views. |
