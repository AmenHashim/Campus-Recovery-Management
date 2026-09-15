# Features

Every module in CPRMS, grouped by the role that uses it, followed by the three cross-cutting
systems (matching, notifications, audit).

- [Student / Staff](#student--staff)
- [Lost & Found Officer](#lost--found-officer)
- [Super Admin](#super-admin)
- [Shared: Profile](#shared-profile)
- [Shared: Search](#shared-search)
- [Matching engine](#matching-engine)
- [Notifications](#notifications)
- [Audit log](#audit-log)

---

## Student / Staff

Controllers: `Student\ItemController`, `Student\ClaimController`,
`Student\NotificationController`, `Student\DashboardController`.

| Feature | Route | What it does |
|---------|-------|--------------|
| **Dashboard** | `GET /student/dashboard` | Reputation points, item/claim/notification counts |
| **Report an item** | `GET/POST /student/report` | One form for both lost & found (a `type` toggle). On submit, the item is created and the **matching engine runs immediately**; the user is told how many potential matches were found. Category is a managed dropdown; location is a free-text field with managed suggestions; optional photo upload. |
| **Dashboard** (activity) | `GET /student/dashboard` | Quick actions plus the user's latest reports and latest claims, and a count of found items added this week. |
| **Browse & search** | `GET /student/browse` | Paginated grid of open/matched **found** items. Keyword search covers name, description, category and location, backed by a **type-ahead** (`/student/browse/suggestions`) that also offers category filter shortcuts. Category filter chips. |
| **Claim an item** | `POST /student/items/{item}/claim` | Submits a claim on a found item, generates a unique token, marks the item `claimed`, and notifies the finder **and all officers**. Guards against claiming your own item, unavailable items, or duplicate pending claims. |
| **My claims** | `GET /student/claims` | The user's claims filterable by outcome, each showing the **collection token**, the next step, and — for a rejected claim — the officer's reason and who reviewed it. |
| **My reports** | `GET /student/my-reports` | Everything the user has reported, with type & lifecycle status, a claim count per item, lost/found filter chips and a searchable type-ahead over their own reports. |
| **Notifications** | `GET /student/notifications` | Match-found, claim-submitted, claim-verified/rejected messages; mark-as-read and **mark all as read**. |

---

## Lost & Found Officer

Controllers: `Officer\ItemController`, `Officer\ClaimController`,
`Officer\NotificationController`, `Officer\DashboardController`.

| Feature | Route | What it does |
|---------|-------|--------------|
| **Dashboard** | `GET /officer/dashboard` | Live counts: pending claims, items in storage, guest reports filed, unread notifications. |
| **File a guest report** | `GET/POST /officer/guest-reports` | Files a lost/found report for a **walk-in guest** who has no account. Creates a `GuestReporter` record (name, contact, ID) and an item with `filed_by` = the officer. Runs the matching engine, same as a student report. |
| **Item intake / storage** | `GET /officer/intake` | A storage board of **found** items filterable by status (in storage / claim pending / returned / closed). Officers can **mark an item returned** or **close** an item — closing reveals a required reason that is written to the audit trail. |
| **Verify claims** | `GET /officer/claims` + `POST .../{claim}/verify` \| `.../reject` | Lists claims (pending first), filterable by outcome and searchable by **token, claimant name, or reg. no.** (with type-ahead). Verify and Reject sit side by side; choosing Reject reveals the required reason before anything is submitted. Verifying records the outcome, marks the item `returned`, awards the finder **+10 reputation**, and notifies the claimant. Rejecting requires a reason, reopens the item, and notifies the claimant. |
| **Notifications** | `GET /officer/notifications` | New-claim-awaiting-verification alerts and other office updates. |

---

## Super Admin

Controllers: `Admin\UserController`, `Admin\ReferenceController`,
`Admin\AnalyticsController`, `Admin\AuditController`, `Admin\DashboardController`.

| Feature | Route | What it does |
|---------|-------|--------------|
| **Dashboard** | `GET /admin/dashboard` | Total users, items in system, open claims, audit entries. |
| **User management** | `GET /admin/users` + row actions | Search (with type-ahead) and filter users by role. Each row has a **⋮ action menu**: change role (student/staff/officer/admin), suspend/reactivate, and **delete** — a soft delete that keeps every record and can be undone from the **Deleted** filter. The admin's own row is protected from self-suspension, self-role-change and self-deletion. |
| **Reference data** | `GET /admin/reference` + CRUD | Manage the **category** and **location** lists that populate the report forms. Add / rename / re-icon / hide (deactivate) / delete. An in-use entry **cannot be deleted** — it must be deactivated instead — so historical items keep a valid value. |
| **Analytics** | `GET /admin/analytics` | Read-only KPIs and charts: **recovery rate** (returned ÷ found), **claim approval rate**, found-item lifecycle breakdown, claim outcomes, reports by category & location, a 6-month report trend, and matching-engine stats (matches generated, avg confidence, high-confidence count). Pure-CSS charts, no external library. |
| **Audit log** | `GET /admin/audit` | Read-only, paginated, filterable (by action group) and searchable view of the immutable activity trail. |

---

## Shared: Profile

Controller: `ProfileController` · routes `GET/PATCH /profile` (any authenticated,
active user). One consistent screen for every role, rendered in the app's dashboard layout.

- **Header card** — avatar (uploaded photo or auto-generated initials), name, role & status
  badges, reg. no., email, "member since", and reputation points (students only).
- **Edit profile** — change name, phone, email, and **upload/replace/remove a profile
  picture** (stored on the `public` disk, ≤ 2 MB, with live preview). Registration number and
  role are read-only (managed by the office/admin).
- **Change password** — current + new + confirm.
- **Closing an account** — users cannot delete their own account. An account is an
  institutional record tied to items, claims and the audit trail, so closure is an **admin
  action** and a **soft delete**: the user loses access immediately, the row and all its
  history stay in the database, and an admin can restore it.

The uploaded avatar also appears in the app header, linking back to the profile.

---

## Shared: Search

Component `<x-search-bar>` · controller `SearchSuggestionController`.

Every search bar in the app (browse, my reports, officer claims, admin users, audit log) is
the same component. It renders the form plus a **type-ahead**: from two characters, a
debounced request hits that page's `…/suggestions` endpoint and shows up to 8 matches with an
icon, the matched text highlighted, and a line of context (role, location, claimant…).

- Arrow keys move, Enter picks, Escape closes, clicking outside dismisses; picking a
  suggestion fills the box and runs the search.
- Some rows are **filter shortcuts** rather than search terms — e.g. a category hit on Browse
  links straight to that filtered list (marked with a filter icon).
- Suggestion endpoints live inside the same role-gated route groups as the pages they serve,
  so a student can't reach the user or audit suggestions.
- Active filters ride along as hidden inputs, so searching never drops the chip you picked,
  and a ✕ clears the query while keeping the filter.

---

## Matching engine

`App\Services\MatchingEngine` — a transparent, weighted v1 engine (no AI/image matching).
When an item is reported, it is scored against every open/matched item of the **opposite
type**; pairs scoring above a threshold become `ItemMatch` rows, and a pair scoring high
enough additionally notifies both reporters.

**Weights** (sum to 100%):

| Signal | Weight |
|--------|--------|
| Text similarity (name + description) | 30% |
| Category match | 20% |
| Location similarity (exact or substring) | 20% |
| Date proximity | 15% |
| Shared attribute keywords (colours/brands) | 15% |

**Thresholds** (FR-C3) — the score decides both persistence *and* whether anyone is told:

| Score | Band | Persisted? | Notifies? |
|-------|------|------------|-----------|
| **≥ 75** | Likely Match | Yes | **Yes** — both reporters get a `match_found` notification |
| **40 – 74** | Possible Match | Yes | No — it surfaces passively in the UI only |
| **< 40** | — | No | No |

Any persisted match moves both items to `matched`. On re-scoring, a pair only notifies the
first time it crosses into the Likely band, so repeated runs cannot spam the same users.

Guest reporters have no login, so they are never notified (BR-02); the Office reaches them
through the contact details retained on the guest record.

---

## Notifications

A lightweight in-app system (`AppNotification` model / `app_notifications` table) — separate
from Laravel's built-in notifications. Each row has a `type`, title, message, and `read_at`.

Types in use: `match_found`, `claim_submitted`, `claim_verified`, `claim_rejected`. Both the
student and officer sides have a notifications page with an unread indicator and a
mark-as-read action. Claim submissions notify the finder **and** every officer.

---

## Audit log

`App\Models\AuditLog` — an **immutable** activity trail (FR-F4). Entries are only ever
inserted and read (no `updated_at`, no delete UI). Written through one helper:

```php
AuditLog::record('claim.verified', "Verified claim UNI-… for \"Black HP Laptop\"", $claim);
```

Each entry captures the **actor** (or `System`), a machine `action` key, a human
description, the client **IP**, and an optional polymorphic **subject** (the affected Item,
Claim, User, Category, …).

Captured across the app: item reports (student & guest), intake return/close, claim
submit/verify/reject, user suspend/reactivate/role-change, all reference-data CRUD, and
**login/logout** (via auth events wired in `AppServiceProvider`). The admin viewer groups
actions into **Auth / Items / Claims / Users / Reference** for filtering.
