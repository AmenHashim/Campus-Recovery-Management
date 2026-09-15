# Architecture

CPRMS is a conventional server-rendered Laravel application: HTTP requests hit role-scoped
routes, controllers orchestrate Eloquent models and a couple of domain services, and Blade
renders the response through one of two layout components. There is no SPA and no API layer
(the exception handler is API-aware but no API routes are defined).

## Layers

```
Request
  │
  ▼
routes/web.php ──────────── role-grouped route definitions
  │
  ▼
Middleware  ─────────────── auth · active (not suspended) · role:<name>
  │
  ▼
Controllers ─────────────── Student\* · Officer\* · Admin\* · ProfileController
  │           │
  │           └── Form Requests (validation)  e.g. ProfileUpdateRequest
  ▼
Services ────────────────── MatchingEngine · TokenGenerator
  │
  ▼
Models (Eloquent) ───────── User, Item, Claim, GuestReporter, ItemMatch,
  │                          AppNotification, Category, Location, AuditLog
  ▼
Database (MySQL)
  │
  ▼
Blade views  ────────────── <x-dashboard-layout> / <x-auth-layout>
```

## Request lifecycle (example: an officer verifies a claim)

1. `POST /officer/claims/{claim}/verify` matches a route inside the
   `role:officer` group in `routes/web.php`.
2. Middleware runs: `auth` (logged in) → `active` (account not suspended) →
   `role:officer` (Spatie gate).
3. `Officer\ClaimController@verify` updates the claim + item status, awards the finder
   reputation points, creates an `AppNotification` for the claimant, and writes an
   `AuditLog` entry.
4. A redirect back with a flash `status` message re-renders the claims list via
   `<x-dashboard-layout>`.

## Roles & permissions

Authorization is owned by **spatie/laravel-permission**, seeded by `RoleSeeder`. Roles are
referenced through constants on the `User` model — never raw strings.

| Constant | Value | Who |
|----------|-------|-----|
| `User::ROLE_STUDENT_STAFF` | `student_staff` | Students and staff (same role) |
| `User::ROLE_OFFICER` | `officer` | Lost & Found office staff |
| `User::ROLE_ADMIN` | `admin` | System administrators |

`student_staff` is one role shared by two **identities** distinguished by the `user_type`
column (`student` / `staff`); officers and admins have `user_type = null`.

Permissions (declarative, defined even where a feature check is not yet used):

```
report items · view items · file guest reports · manage item status
submit claims · verify claims
manage users · manage reference data · view analytics · view audit logs
```

`RoleSeeder` assigns permission subsets: students get report/view/submit; officers get
view/file/manage-status/verify; admin gets everything.

### Where users land

`User::homeRoute()` maps role → dashboard: admin → `admin.dashboard`, officer →
`officer.dashboard`, otherwise `student.dashboard`. The root `/` route redirects an
authenticated user there, or shows the marketing `welcome` page.

## Middleware

Registered in `bootstrap/app.php` (Laravel 11+ style — there is no `Http/Kernel.php`):

| Alias | Class | Purpose |
|-------|-------|---------|
| `role` / `permission` / `role_or_permission` | Spatie middleware | Role/permission gates |
| `active` | `App\Http\Middleware\EnsureUserIsActive` | Logs out & blocks users suspended mid-session |

Every role group is protected by `['auth', 'active', 'role:<name>']`; the shared profile
routes use `['auth', 'active']`.

## Account status

Users are never hard-deleted by admins — they are **suspended** (`status = suspended`) and
reactivated. The `active` middleware enforces this on every request. (Users may still delete
their *own* account from the profile page.)

## Domain services

- **`MatchingEngine`** — a weighted scoring engine that compares a new item against
  opposite-type items and persists `ItemMatch` rows above a threshold. Called synchronously
  from the item-report controllers. See [FEATURES → Matching engine](FEATURES.md#matching-engine).
- **`TokenGenerator`** — produces unique, human-readable claim tokens (e.g. `UNI-3805-A128`)
  that the office checks against a claimant in person.

## Conventions

- **Layout components.** Authenticated screens extend `<x-dashboard-layout>` (sidebar +
  header, role-aware menu). Auth screens use `<x-auth-layout>`. The role menu is built once
  inside `dashboard-layout.blade.php`.
- **Styling.** A hand-written design system in `public/assets/css/` — reusable classes like
  `.glass-card`, `.stat-card`, `.badge-*`, `.form-group`, `.btn-*`. Theme-aware (light/dark)
  via CSS custom properties.
- **Requirement traceability.** Comments reference requirement codes (`FR-*`, `BR-*`) from
  the project's requirements specification, e.g. `// FR-F1` on the suspension middleware.
- **Reference data as strings.** `items.category` / `items.location` are stored as plain
  strings, not foreign keys — the `categories` / `locations` tables *drive the form options*
  but do not constrain historical rows (so retiring an entry never orphans past items, and
  the matching engine can keep doing substring comparison on locations).
