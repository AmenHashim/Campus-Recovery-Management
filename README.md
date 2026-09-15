# CPRMS — Campus Property Recovery Management System

A web application for reporting, matching, and recovering lost property at the
**University Recovery System, Dar es Salaam**. Students and staff report lost or
found items, a weighted matching engine surfaces likely pairs automatically, and Lost &
Found officers verify claims in person and hand items back to their owners.

Built with **Laravel 13**, **MySQL**, and **spatie/laravel-permission** for role-based access.

---

## Table of contents

- [What it does](#what-it-does)
- [Tech stack](#tech-stack)
- [Quick start](#quick-start)
- [Default accounts](#default-accounts)
- [Project structure](#project-structure)
- [Documentation](#documentation)
- [Implementation status](#implementation-status)

---

## What it does

Three roles share one system:

| Role | Can do |
|------|--------|
| **Student / Staff** | Report lost & found items, browse/search the found pool, claim an item, track their claims with a collection token, receive notifications |
| **Lost & Found Officer** | File reports for walk-in guests, manage item intake & storage status, verify/reject claims in person, receive notifications |
| **Super Admin** | Manage user accounts & roles (suspend, soft-delete, restore), manage categories & locations, view recovery analytics, review the audit log |

Account deletion is **admin-only and soft**: users cannot delete their own accounts, and a
deleted account keeps every item, claim, and audit row it created.

Cross-cutting systems: a **weighted matching engine** that pairs lost and found reports, an
in-app **notification** system, and an immutable **audit log** of significant actions.

See **[docs/FEATURES.md](docs/FEATURES.md)** for the full breakdown.

---

## Tech stack

| Layer | Choice |
|-------|--------|
| Framework | Laravel 13 (PHP 8.3+) |
| Auth scaffolding | Laravel Breeze (Blade) |
| Authorization | spatie/laravel-permission 8 (roles + permissions) |
| Database | MySQL (SQLite used for the automated test suite) |
| Frontend | Blade + a hand-written CSS design system (glass-morphism), Tabler Icons, Inter font |
| Build | Vite |

The UI is **not** Tailwind-driven in the app screens — it uses a custom stylesheet
(`public/assets/css/style.css` + `customized.css`) rendered through two Blade layout
components, `<x-dashboard-layout>` (authenticated app) and `<x-auth-layout>` (login/register).

---

## Quick start

> Full details, troubleshooting, and seeding options are in **[docs/SETUP.md](docs/SETUP.md)**.

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
#   → set DB_CONNECTION=mysql and the DB_* credentials in .env

# 3. Database (creates schema + seeds demo data)
php artisan migrate:fresh --seed

# 4. Storage symlink (for item images & profile avatars)
php artisan storage:link

# 5. Build assets & run
npm run build        # or: npm run dev
php artisan serve
```

Visit <http://localhost:8000>.

---

## Default accounts

Seeded by `UserSeeder`. Officer and Admin accounts can never be self-registered
(they only exist via seeding or admin provisioning).

| Role | Login (email or reg. no.) | Password |
|------|---------------------------|----------|
| Super Admin | `admin@mwangatech.ac.tz` | `Admin@12345` |
| Officer | `office@mwangatech.ac.tz` | `Officer@12345` |
| Student | `biggie@gmail.com` | `biggie@12345` |
| Staff | `allen@gmail.com` | `allen@12345` |

A full seed also creates ~500 additional student/staff accounts (password `password`) plus
thousands of items, claims, notifications, and audit entries for realistic testing.

---

## Project structure

```
app/
  Http/Controllers/
    Student/    — report, browse, claim, notifications, dashboard
    Officer/    — guest reports, item intake, claim verification, notifications, dashboard
    Admin/      — users, reference data, analytics, audit, dashboard
    ProfileController.php          — shared profile (edit, avatar, password)
    SearchSuggestionController.php — type-ahead for every search bar
  Http/Middleware/EnsureUserIsActive.php   — blocks suspended users mid-session
  Models/       — User, Item, GuestReporter, ItemMatch, Claim, AppNotification,
                  Category, Location, AuditLog
  Notifications/ — branded account email (verification, password reset)
  Services/     — MatchingEngine, TokenGenerator
database/
  migrations/   — schema, dated
  seeders/      — Role, Permission, ReferenceData, User, Item, Claim, AuditLog
resources/views/
  components/   — dashboard-layout, auth-layout (the two shells),
                  search-bar, map-picker, widgets/ (stat, panel, bar/column chart)
  student/  officer/  admin/  profile/  auth/  emails/
routes/web.php  — all app routes, grouped by role
tests/Feature/  — feature tests (see Implementation status)
docs/           — this documentation
```

---

## Documentation

| Document | Contents |
|----------|----------|
| **[docs/SETUP.md](docs/SETUP.md)** | Requirements, installation, configuration, seeding, running, tests |
| **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** | Layers, request lifecycle, roles & permissions, middleware, conventions |
| **[docs/FEATURES.md](docs/FEATURES.md)** | Every module by role + the matching engine, notifications, and audit log |
| **[docs/DATA-MODEL.md](docs/DATA-MODEL.md)** | Entity-relationship diagram and a table-by-table schema reference |

---

## Implementation status

All primary modules are implemented and working:

- ✅ Authentication & role-based routing (Student/Staff, Officer, Admin), dual-credential
  login (email **or** registration number), suspension enforced at login *and* mid-session
- ✅ Item reporting (lost & found) with optional map pin + browse/search
- ✅ Weighted matching engine (20/20/15/30/15) with the FR-C3 notify thresholds
- ✅ Claim submission with collection token, verification & rejection-with-reason
- ✅ Officer guest-report filing, item intake/storage, close-with-reason
- ✅ Admin user management (role change, suspend, soft delete + restore), reference data,
  analytics, audit log
- ✅ In-app notifications (CPRMS's own, not the framework's) + branded account email
- ✅ Role dashboards with summary widgets, tables and charts
- ✅ Type-ahead suggestions on every search bar, role-gated per endpoint
- ✅ User profiles with avatar upload, editing, password change

### Test suite

```bash
php artisan test
```

84 feature tests / 214 assertions, all passing. `PageSmokeTest` renders every
authenticated screen for its role, so a broken layout or component fails fast.

### Known limitations (deferred to Version 2)

- **FR-B5** — users cannot yet edit their own open reports; corrections go through the Office.
- **FR-C4** — duplicate detection happens implicitly through match scoring, but is not
  surfaced as a distinct "possible duplicate" flag with officer merge tooling.
- **Match review UI** — `item_matches` records `reviewed_by`, but there is no dedicated
  officer match-review screen yet.
- **Synchronous matching** — fine at campus volume; queued jobs are the documented scale path.
- Version 1 exclusions stand: no mobile app, image recognition, OCR, SMS gateway,
  multi-campus, or ERP integration.
