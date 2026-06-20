# Sprint 4 Therapist Commissions

## Scope

CPSMS-41 adds automatic therapist commission computation to the paid cash transaction workflow. It includes calculation, duplicate protection, management status actions, and owner-scoped therapist visibility. Full sales and commission reporting, charts, payroll, HR, online payments, promotions, RFM analysis, and reviews remain outside this task.

## Qualifying Transactions

A commission is created only when a transaction:

- Has payment status `paid`.
- Belongs to a completed appointment.
- Has an assigned therapist profile.
- Does not already have a commission record.

Pending and void transactions create no commission. The current cash module does not expose transaction status editing after creation, so a paid transaction cannot currently be changed to void. Management can void a pending commission after review. If transaction voiding is introduced later, that workflow must also synchronize its existing commission to void.

## Calculation Basis

Commission uses the immutable transaction `subtotal` before discount. This preserves therapist compensation for the completed service when Casa Paraiso applies a customer discount.

Therapist profile rates use percentage format. For example, a `20.00` rate means 20 percent, not decimal `0.20`.

The formula is:

```text
commission amount = transaction subtotal x commission rate / 100
```

`TherapistCommissionCalculator` converts the subtotal to integer cents and the percentage to hundredths of a percent. It rounds the final result to the nearest cent. A PHP 850.00 subtotal at 20.00 percent produces PHP 170.00. A PHP 999.99 subtotal at 12.50 percent produces PHP 125.00 after rounding.

The schema does not contain a separate basis snapshot column. The linked transaction already stores the immutable subtotal basis, while `therapist_commissions` snapshots `commission_rate` and `commission_amount`.

## Atomic Recording

`TransactionRecorder` invokes `TherapistCommissionRecorder` after creating a paid transaction and before the surrounding database transaction commits. A commission error therefore rolls back the cash transaction too.

The commission recorder locks the transaction row and checks for an existing `transaction_id` before insertion. Repeated or competing recorder calls return the existing commission instead of creating duplicates.

## Status Behavior

Commission statuses are:

- `pending`: automatically assigned when a qualifying paid transaction is recorded.
- `paid`: management-confirmed payout; sets `paid_at` to the current timestamp.
- `void`: management-cancelled commission; leaves `paid_at` empty.

Only `pending -> paid` and `pending -> void` transitions are permitted. Paid and void records are terminal and cannot return to pending.

## Routes

### Management

| Method | Route | Name |
| --- | --- | --- |
| GET | `/management/commissions` | `management.commissions.index` |
| GET | `/management/commissions/{commission}` | `management.commissions.show` |
| PATCH | `/management/commissions/{commission}/status` | `management.commissions.update-status` |

### Therapist

| Method | Route | Name |
| --- | --- | --- |
| GET | `/therapist/commissions` | `therapist.commissions.index` |
| GET | `/therapist/commissions/{commission}` | `therapist.commissions.show` |

## Visibility and Security

- Management users can view all commissions and update pending statuses.
- Therapists can view only commissions linked to their own therapist profile.
- Accessing another therapist's commission returns `404 Not Found`.
- Therapist accounts without a linked profile receive `403 Forbidden`.
- Customer users cannot access management or therapist commission routes.
- Guests are redirected to login.
- Status requests authorize management users and validate terminal target statuses.

## Interface

Management receives a commission ledger, calculation detail, linked cash receipt, and pending status form. Therapists receive a read-only ledger and detail page showing the service, customer, status, subtotal basis, rate snapshot, amount, and paid date. The pages use the Casa Paraiso Blade components and responsive table patterns.

## Tests

- `tests/Feature/Management/TherapistCommissionTest.php` covers calculation, rounding, paid/pending/void transaction behavior, duplicate prevention, management visibility, status transitions, paid timestamps, and RBAC.
- `tests/Feature/Therapist/TherapistCommissionVisibilityTest.php` covers owner-only lists/details, all supported statuses, cross-therapist denial, guest redirects, role restrictions, and missing-profile behavior.
- `tests/Feature/Management/TransactionRecordingTest.php` confirms CPSMS-40 transaction creation remains operational with automatic paid commission creation.
