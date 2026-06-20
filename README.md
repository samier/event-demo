# Event Visuals — Coding Test

A small web app for browsing events two different ways, with sign-ups and reminder
emails. Built on the provided Laravel starter kit.

## What this app does (in plain terms)

- **Two ways to browse events:**
  - **Gallery** — a colourful grid of event cards (`/events-visual-1`).
  - **Agenda** — a dark, day-by-day timeline (`/events-visual-2`).
- **Each event** shows a title, description, pictures, a real place name, and the
  date/time (in both the event's local time and yours).
- **Filter** events by date and by city (plus search and event type).
- **Sign up** for an event — you get a confirmation email, appear on the event's
  attendee list, and receive reminder emails 3 days and 24 hours before it starts.
- **Dashboard** — a quick overview of events and recent sign-ups.

For the reasoning behind each choice, see **[DECISIONS.md](DECISIONS.md)** (written in
plain language). For a diagram of how the data is organised, see
**[docs/ERD.md](docs/ERD.md)**.

## What you need

- PHP 8.3+ with these extensions turned on: `pdo_sqlite`, `mbstring`, `intl`, `gd`,
  `curl`, `openssl`, `fileinfo`.
- Node.js 20+ and npm.

## Setup

Each line below has a short note explaining what it does.

```bash
# 1. Install the project's building blocks
composer install        # the back-end (PHP) parts
npm install             # the front-end (browser) parts

# 2. Create the settings file and a security key
cp .env.example .env
php artisan key:generate

# 3. Set up the database and fill it with sample events.
#    SEED_ROWS limits how many sample events are created so it loads quickly.
touch database/database.sqlite
php artisan migrate
SEED_ROWS=3000 php artisan db:seed

# 4. (Optional) regenerate the placeholder event pictures
php artisan app:generate-event-images

# 5. Prepare the browser files
npm run build
```

> On Windows, make sure your PHP program is on your system PATH before running
> `npm run build` (the build step needs to call PHP).

## Running it

```bash
php artisan serve        # then open http://127.0.0.1:8000
```

That's all you need to view the app. (If you're actively editing the look of the
pages, you can also run `npm run dev` in a second window for instant updates — but it
isn't required just to use the app.)

Open `/events-visual-1` for the Gallery and `/events-visual-2` for the Agenda.

## Emails — and how to verify them

By default the app **writes emails to a file** instead of sending them, so you can read
every confirmation and reminder in `storage/logs/laravel.log` without setting up a real
email account.

**Email activity & testing page** (easiest — local only): start the app and open

```
http://127.0.0.1:8000/dev/emails
```

It's also linked from the **Dashboard**. The page shows, per event, how many
confirmation and reminder emails have been sent (e.g. `5/5`), lets you **preview** the
exact confirmation / 3-day / 24-hour emails in one click, and gives you copy-paste
commands to trigger reminders.

**Send / trigger reminders by hand.** The reminder command is safe to run repeatedly —
it never sends the same reminder twice — and has options that make it easy to test:

```bash
php artisan events:send-reminders                       # send anything currently due
php artisan events:send-reminders --pretend             # preview who WOULD be emailed
php artisan events:send-reminders --event=<EVENT_ID> --force   # send now for one event
php artisan events:send-reminders --window=3-day        # only the 3-day window
```

The command prints a table of every recipient and which reminder they got.

In a real deployment, Laravel's scheduler runs the reminders automatically every hour:

```bash
php artisan schedule:work
```

## Checking the quality

```bash
php artisan test            # automated tests (events, filtering, sign-ups, reminders)
npm run lint:check          # checks the front-end code style
npm run format:check        # checks formatting
npm run types:check         # checks the front-end for type mistakes
```

## Where things live (for developers)

| What it does | File(s) |
| --- | --- |
| Turn coordinates into a place name + time zone | `app/Support/Geocoder.php`, `app/Support/Cities.php` |
| Pick local placeholder pictures for an event | `app/Console/Commands/GenerateEventImages.php`, `app/Support/EventImages.php` |
| Shape event data for the screen | `app/Http/Resources/EventResource.php` |
| List + filter events, and the event detail page | `app/Http/Controllers/EventController.php` |
| Sign-ups + emails | `app/Http/Controllers/AttendeeController.php`, `app/Mail/*`, `resources/views/mail/*` |
| Reminder emails | `app/Console/Commands/SendEventReminders.php`, `routes/console.php` |
| The two browsing pages + event page | `resources/js/pages/Events/` |
| Shared front-end pieces | `resources/js/composables/`, `resources/js/components/events/` |
