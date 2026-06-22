# Event Visuals — Coding Test

A small web app for browsing events two different ways, with sign-ups and reminder
emails. Built on the provided Laravel starter kit.

## What this app does (in plain terms)

- **Two ways to browse events:**
  - **Gallery** — a card grid (`/events-visual-1`).
  - **Agenda** — a day-by-day timeline with a featured “next up” event (`/events-visual-2`).
- **Each event** shows a title, description, pictures, a human-readable place name, and
  the date/time in the event’s local timezone.
- **Filter** events by date, city (searchable dropdown), event type, and free-text search.
- **Sign up** for an event — you get a confirmation email, appear on the attendee list,
  and receive reminder emails 3 days and 24 hours before it starts.
- **Dashboard** — a quick overview of events and recent sign-ups.

For the reasoning behind each choice, see **[DECISIONS.md](DECISIONS.md)**. For a
diagram of how the data is organised, see **[docs/ERD.md](docs/ERD.md)**.

## City anchors (locations)

Events only store raw latitude/longitude. Place names and timezones come from a
**`city_anchors` table** — the database replacement for the old hard-coded city list.

| Step | What happens |
| --- | --- |
| **Coordinates** | `database/data/city_anchor_coordinates.php` — 75 fixed lat/lng pairs (US, Canada, Mexico, Europe, global hubs) |
| **Seed anchors** | `CityAnchorSeeder` geocodes each point via Nominatim + timeapi.io (`config/geocoder.php`) and saves to the table as it goes |
| **Seed events** | `EventSeeder` picks a random anchor, jitters ±0.5°, and inserts the event |
| **At runtime** | `CityAnchor::resolveAddress()` finds the nearest anchor — no external API calls |
| **Dropdown / filters** | `CityAnchor::filterOptions()` and `boundingBoxForCity()` |

Re-seed anchors only:

```bash
php artisan db:seed --class=CityAnchorSeeder
```

## What you need

- PHP 8.3+ with: `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `intl`, `gd`, `curl`,
  `openssl`, `fileinfo`.
- Node.js 20+ and npm.
- Network access during **`db:seed`** (city anchor geocoding calls OpenStreetMap Nominatim
  and timeapi.io).

## Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
touch database/database.sqlite          # skip if using MySQL — configure DB_* in .env
php artisan migrate

# 4. Seed (CityAnchorSeeder runs first, then EventSeeder)
#    Anchor geocoding: 75 points × ~1.1s delay (~90s). Progress prints per city.
#    Use a smaller event count locally:
SEED_ROWS=3000 php artisan db:seed

# 5. (Optional) regenerate placeholder event images
php artisan app:generate-event-images

# 6. Front-end assets
npm run build
```

> **Windows:** ensure PHP is on your PATH before `npm run build` (Vite calls PHP during
> the build).

> **Full dataset:** omit `SEED_ROWS` to seed 1,250,000 events (~2.5 GB). Use
> `SEED_ROWS=50000` (or similar) for day-to-day development.

### Geocoder settings (seed only)

Optional entries in `.env` — see `.env.example`:

- `GEOCODER_USER_AGENT` — required by Nominatim policy
- `GEOCODER_NOMINATIM_DELAY` — seconds between lookups (default `1.1`)

## Running it

```bash
php artisan serve        # http://127.0.0.1:8000
```

- Gallery: `/events-visual-1`
- Agenda: `/events-visual-2`

For live front-end edits, run `npm run dev` in a second terminal (optional).

## Emails — and how to verify them

By default the app **logs emails** to `storage/logs/laravel.log` instead of sending them.

**Email activity page** (local only):

```
http://127.0.0.1:8000/dev/emails
```

Also linked from the Dashboard — preview confirmations/reminders and see send counts.

**Trigger reminders manually:**

```bash
php artisan events:send-reminders
php artisan events:send-reminders --pretend
php artisan events:send-reminders --event=<EVENT_ID> --force
php artisan events:send-reminders --window=3-day
```

In production, the scheduler runs reminders hourly:

```bash
php artisan schedule:work
```

## Checking the quality

```bash
php artisan test
npm run lint:check
npm run format:check
npm run types:check
```

## Where things live (for developers)

| What | File(s) |
| --- | --- |
| City anchor coordinates (seed input) | `database/data/city_anchor_coordinates.php` |
| Geocode anchors at seed time | `database/seeders/CityAnchorSeeder.php`, `app/Support/SeedGeocoder.php`, `config/geocoder.php` |
| Anchor model (nearest, filters, bounding box) | `app/Models/CityAnchor.php` |
| Resolve event lat/lng → address at runtime | `CityAnchor::resolveAddress()` via `app/Http/Resources/EventResource.php` |
| Event feed + filters | `app/Services/EventService.php`, `app/Http/Controllers/EventController.php`, `GET /events/feed` |
| Seed bulk events | `database/seeders/EventSeeder.php` |
| Placeholder images | `app/Support/EventImages.php`, `app/Console/Commands/GenerateEventImages.php` |
| Sign-ups + mail | `app/Http/Controllers/AttendeeController.php`, `app/Mail/*` |
| Reminder emails | `app/Console/Commands/SendEventReminders.php` |
| Gallery + Agenda UI | `resources/js/pages/Events/`, `resources/js/composables/useEventFeed.ts` |
| Searchable city filter | `resources/js/components/events/CitySearchSelect.vue` |
