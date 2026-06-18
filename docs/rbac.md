# Role-Based Access Control

Casa Paraiso uses the `users.role_id` relationship to assign each authenticated user one role: `management`, `therapist`, or `customer`.

## Enforcement

The `role` middleware alias points to `App\Http\Middleware\EnsureUserHasRole`. Role pages use both the standard Laravel `auth` middleware and the required role middleware:

| Route | Required role |
| --- | --- |
| `/management` | `management` |
| `/therapist` | `therapist` |
| `/customer` | `customer` |

Guests are redirected to `/login` by Laravel's authentication middleware. An authenticated user requesting another role's area receives a `403 Forbidden` response.

## Role-Aware Behavior

`App\Models\User` provides `hasRole()`, `isManagement()`, `isTherapist()`, and `isCustomer()` helpers. The dashboard redirect and shared navigation use these helpers so each authenticated user is sent to, and shown a link for, only their assigned role area.

This role boundary should also be applied to future routes added under each area. Detailed permissions within a role can be introduced with Laravel policies or gates when those business modules are implemented.
