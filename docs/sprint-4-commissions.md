# Sprint 4 Therapist Commissions

## Scope

CPSMS-41 adds therapist commission computation for completed appointments with paid cash transactions. It includes automatic calculation, lifecycle synchronization, management monitoring and settlement, therapist-owned visibility, access control, and feature tests. Full sales and commission reports remain deferred to CPSMS-42.

## Routes

| Method | Route | Name | Access |
| --- | --- | --- | --- |
| GET | `/management/commissions` | `management.commissions.index` | Management |
| GET | `/management/commissions/{commission}` | `management.commissions.show` | Management |
| PATCH | `/management/commissions/{commission}/mark-paid` | `management.commissions.mark-paid` | Management |
| GET | `/therapist/commissions` | `therapist.commissions.index` | Owning therapist |
| GET | `/therapist/commissions/{commission}` | `therapist.commissions.show` | Owning therapist |

All routes use the existing `auth` and role middleware. Therapist detail lookup is ownership-scoped and returns `404 Not Found` for another therapist's record.

## Automatic Computation

`TransactionObserver` calls `TherapistCommissionCalculator` after a transaction is created or updated. A transaction qualifies when it:

- Uses the `cash` payment method.
- Has a `paid` payment status.
- Links to a completed appointment.
- Has an assigned therapist profile.

The calculator runs in a database transaction and locks the transaction and existing commission rows while synchronizing. The database also has a unique constraint on `therapist_commissions.transaction_id`, providing final duplicate protection.

## Calculation Rules

- `commission_base_amount` is the transaction `subtotal`, representing the service amount before discount.
- `commission_rate` is a percentage such as `20.00`, not a decimal fraction such as `0.20`.
- The first calculation snapshots the therapist profile's `commission_rate`.
- `commission_amount = commission_base_amount x commission_rate / 100`.
- Calculation is performed in cents and rate basis points, then rounded to the nearest cent.
- Later therapist profile rate changes do not alter the stored snapshot.
- An unpaid commission may follow transaction subtotal corrections using its stored rate snapshot.
- A paid commission is never recalculated by later transaction changes.

## Status Behavior

- A qualifying paid transaction creates or maintains a `pending` commission.
- An initially pending transaction creates no commission until it becomes paid.
- An initially void transaction creates no commission.
- If a transaction becomes pending or void while its commission is pending, that commission becomes `void` and is not payable.
- If the same transaction returns to paid, its existing void commission returns to `pending` without changing the original rate snapshot.
- Management may mark only a pending commission as `paid`; `paid_at` is captured at settlement.
- A paid commission remains paid if the transaction is later edited or voided, preserving the completed payout audit record rather than silently rewriting history.

## Stored Relationships

Each calculated record stores the transaction, appointment, therapist profile, and linked therapist user when available. The profile and user links support management display and therapist ownership checks. The base amount and rate are stored directly so historical calculations remain explainable even when service prices or therapist rates change.

## Views And Filters

Management can list every commission and filter by therapist, commission status, and transaction date range. The detail page shows the source transaction, appointment, customer, service, calculation inputs, amount, and settlement timestamp. Pending records expose the management-only settlement action.

Therapists can filter and view only their own commission list and details. Their pages are read-only and do not render or expose a settlement action.

## Security

- Management commission routes require `role:management`.
- Therapist commission routes require `role:therapist` and ownership-scoped queries.
- `MarkCommissionPaidRequest` independently authorizes management users.
- Settlement locks and rechecks the latest commission and transaction statuses.
- Therapists and customers cannot settle commissions.
- Customers receive `403 Forbidden`; guests are redirected to `/login`.

## Tests

Feature coverage verifies calculation accuracy, percentage snapshots, rate immutability, duplicate prevention, pending/paid/void transitions, paid-record stability, management filtering and settlement, therapist ownership, therapist read-only behavior, customer restrictions, and guest redirects.
