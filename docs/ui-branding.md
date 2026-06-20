# Casa Paraiso UI Branding

## Design Direction

Casa Paraiso uses a modern wellness spa dashboard style. The interface balances the clarity required for daily operations with the warm, quiet atmosphere of a professional body and wellness spa.

The identity is expressed through bold `CASA PARAISO` text, the `Body and Wellness Spa` subtitle, and a simple inline leaf mark. The design does not depend on remote images, fonts, icon packages, or other internet assets.

## Color System

Custom Tailwind CSS tokens are defined in `resources/css/app.css`.

| Role | Token family | Example | Use |
| --- | --- | --- | --- |
| Primary | `cocoa` | `#50362a` | Navigation, primary actions, headings |
| Nature accent | `sage` | `#517049` | Leaf mark, links, focus, positive states |
| Surface | `cream` | `#faf5e9` | Page backgrounds, panels, borders |
| Warm accent | `gold` | `#ae8745` | Highlights and coming-soon labels |
| Critical | Tailwind red | Standard red scale | Errors and destructive actions |

Status colors remain semantic and readable. Active, confirmed, and completed states use sage; pending states use amber; inactive, cancelled, and no-show states use neutral stone; in-progress states use blue.

## Reusable Blade Components

The UI primitives live in `resources/views/components`:

- `page-header.blade.php` provides an eyebrow, title, description, and optional actions.
- `card.blade.php` provides the standard bordered panel and shadow.
- `button.blade.php` supports links or buttons with primary, secondary, subtle, ghost, and danger variants.
- `status-badge.blade.php` maps record and appointment statuses to semantic colors.
- `alert.blade.php` renders info, success, warning, and error feedback.
- `empty-state.blade.php` gives empty collections a consistent message and optional action.
- `form/input.blade.php`, `form/select.blade.php`, and `form/textarea.blade.php` centralize labels, required markers, error messages, focus states, and field styling.

Shared table and detail styles are defined in the `components` layer of `resources/css/app.css` using the `spa-` prefix.

## Layout and Navigation

The main layout includes a sticky branded header, role-aware navigation, unread notification counts, user identity, logout, and a branded footer. Desktop navigation is shown at large breakpoints. Smaller screens use a native disclosure menu so navigation remains usable without additional JavaScript.

Content uses a maximum-width container with responsive horizontal padding. Decorative shapes are CSS and inline SVG only and are hidden from assistive technology.

## Responsive Behavior

- Dashboard cards flow from one column to two or three columns as space allows.
- Forms use one column on mobile and two columns on wider screens.
- Primary and secondary form actions stack on small screens.
- Data tables remain inside horizontally scrollable containers.
- Appointment details switch from stacked definitions to two-column grids.
- Booking and status side panels become sticky only on large screens.
- Navigation changes to a compact disclosure menu below the desktop breakpoint.

## Accessibility

- Text and controls use high-contrast cocoa, sage, amber, and red combinations.
- Interactive controls have visible keyboard focus rings.
- Form validation remains associated with the relevant labeled field.
- Navigation identifies active destinations and uses semantic `nav` landmarks.
- Alerts use `status` or `alert` roles as appropriate.
- Decorative leaf artwork uses `aria-hidden="true"`.
- Buttons meet a minimum touch-friendly height and layouts do not rely on color alone for status labels.

## Scope

The UI work changes Blade templates and Tailwind presentation only. Routes, middleware, controllers, validation, models, appointment scheduling rules, RBAC, notifications, and database structures remain unchanged. Transaction, reporting, promotion, review, payment, SMS, and other later-sprint workflows are represented only as non-interactive coming-soon dashboard cards.
