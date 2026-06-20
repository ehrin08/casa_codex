# Sprint 4 Cash Transactions

## Scope

CPSMS-40 adds management-side over-the-counter cash transaction recording for completed appointments. It includes transaction selection, validation, listing, and receipt-style details. Commission integration is provided by CPSMS-41; online payments, payment gateways, reports, promotions, RFM analysis, reviews, SMS, and email delivery remain outside this workflow.

## Routes

All transaction routes require both `auth` and `role:management` middleware.

| Method | Route | Name | Purpose |
| --- | --- | --- | --- |
| GET | `/management/transactions` | `management.transactions.index` | List recorded transactions |
| GET | `/management/transactions/create` | `management.transactions.create` | Select an eligible appointment or show its cash form |
| POST | `/management/transactions` | `management.transactions.store` | Validate and record the transaction |
| GET | `/management/transactions/{transaction}` | `management.transactions.show` | Show the receipt |

An appointment shortcut uses the create route with an `appointment_id` query parameter. Completed appointment details show this shortcut until a transaction exists, then show a receipt link instead.

## Eligibility and Pricing

An appointment is eligible only when:

- Its status is `completed`.
- It does not already have a transaction.
- It has a stored service price snapshot or a related service price.

The appointment's `service_price_snapshot` is authoritative when present. The related `services.price` value is only a fallback for older records without a snapshot.

## Money Calculation

The browser submits the appointment, discount, status, transaction date, optional notes, and cash tendered when paid. It never submits an authoritative subtotal or total.

The server:

1. Resolves the subtotal from the appointment.
2. Converts monetary inputs to integer cents for calculation.
3. Confirms the discount is between zero and the subtotal.
4. Calculates `total_amount = subtotal - discount_amount`.
5. For a paid transaction, confirms cash tendered covers the total and calculates `change_due = amount_tendered - total_amount`.
6. Stores `cash` as the payment method.

Valid payment statuses are `pending`, `paid`, and `void`. Tendered cash and change are stored only for `paid` transactions.

## Duplicate Protection

The form request detects an existing appointment transaction for immediate feedback. The recorder then opens a database transaction, locks the appointment row with `lockForUpdate`, and repeats both the completed-status and duplicate checks. Competing requests for one appointment therefore serialize before insertion without requiring a schema change.

## Stored Relationships

Each recorded transaction links to:

- The completed appointment.
- The appointment's customer profile when available.
- The authenticated management user as cashier.

Appointment relationships provide the therapist, service, schedule, and snapshot details displayed by the list and receipt.

## Receipt

The receipt displays Casa Paraiso branding, receipt reference, transaction date, payment status, customer, cashier, service, therapist, appointment schedule, payment method, subtotal, discount, total, cash tendered, change due, and optional notes.

## Security

- Guests are redirected to login.
- Therapist and customer users receive `403 Forbidden`.
- `StoreTransactionRequest` authorizes management users and validates submitted data.
- `TransactionRecorder` independently rejects non-management callers.
- Financial totals are always recomputed on the server.

## Commission Integration

After CPSMS-41, a qualifying paid transaction creates one pending therapist commission inside the same database transaction. Pending and void transactions create no commission. Commission calculation and status behavior are documented in `docs/sprint-4-commissions.md`.

## Tests

`tests/Feature/Management/TransactionRecordingTest.php` covers management access, guest redirects, role restrictions, completed appointment eligibility, ineligible statuses, snapshot pricing, service-price fallback, discounts, cash tender and change, payment status validation, duplicate prevention, receipt rendering, and paid transaction commission integration.
