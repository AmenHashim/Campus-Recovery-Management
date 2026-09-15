# Setup & Installation

This guide gets CPRMS running locally and explains the seeding and testing workflow.

## Requirements

| Tool | Version |
|------|---------|
| PHP | 8.3 or newer (developed on 8.4) |
| Composer | 2.x |
| Node.js + npm | 18+ (for Vite asset build) |
| Database | MySQL 8 (or MariaDB). SQLite also works and is used by the test suite. |

PHP extensions: the usual Laravel set (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`,
`ctype`, `json`). The optional avatar test also uses `gd`, but the app itself does not
require it.

## Installation

```bash
git clone <repo-url> losty
cd losty

composer install
npm install

cp .env.example .env
php artisan key:generate
```

## Configure the database

The committed `.env.example` defaults to `sqlite`. For the full app, use MySQL — create a
database (e.g. `database_name`) and set:

```dotenv
APP_NAME="Campus Lost & Found"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lost_found
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
```

## Migrate & seed

```bash
php artisan migrate:fresh --seed
```

This runs every migration and then the seeders in dependency order (see
`database/seeders/DatabaseSeeder.php`):

| Seeder | Creates |
|--------|---------|
| `RoleSeeder` | The 3 roles + 10 permissions |
| `ReferenceDataSeeder` | 8 categories, 14 campus locations |
| `UserSeeder` | 4 named demo accounts + 2 extra officers + ~500 bulk student/staff |
| `ItemSeeder` | ~2,000 "noise" items + 50 crafted lost/found match pairs (run through the real matching engine) |
| `ClaimSeeder` | 300 claims walked through submit → verify/reject, with notifications |
| `AuditLogSeeder` | ~768 audit entries backfilled from the seeded claims & guest reports |

A full seed takes roughly 1–2 minutes. To seed a single set later, use e.g.
`php artisan db:seed --class=ReferenceDataSeeder`.

> **Tuning volumes:** each data seeder exposes constants at the top (`BULK_STUDENT_STAFF`,
> `TOTAL_NOISE_ITEMS`, `MATCH_PAIRS`, `TOTAL_CLAIMS`) — adjust them to seed a lighter or
> heavier dataset.

## Storage symlink

Item photos and profile avatars are stored on the `public` disk. Create the symlink once:

```bash
php artisan storage:link
```

Uploads then live in `storage/app/public/{items,avatars}` and are served from
`/storage/...`.

## Build assets & run

```bash
npm run build        # production build  (or `npm run dev` for HMR)
php artisan serve
```

Open <http://localhost:8000>. See [default accounts](../README.md#default-accounts) to log
in.

## Running the tests

The test suite runs against an **in-memory SQLite** database (configured in `phpunit.xml`),
so it does not touch your MySQL data:

```bash
php artisan test
```

Feature tests cover the profile module (info update, avatar upload/removal, account
deletion). Note the pre-existing stock-Breeze auth tests that reference a missing
`dashboard` route — see [README → Known limitation](../README.md#implementation-status).

## Common tasks

```bash
php artisan migrate:fresh --seed   # reset & reseed everything
php artisan route:list             # inspect all routes
php artisan tinker                 # REPL against the app/db
php artisan view:clear             # clear compiled Blade cache
```
