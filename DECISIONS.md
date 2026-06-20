# Decisions & notes (in plain language)

This document explains, in everyday terms, what the coding test asked for, what I
built, and why I made the choices I did. No technical background needed.

The goal was **quality over quantity**: two genuinely different ways to browse the
events, plus all the supporting pieces (pictures, real place names, sensible times,
filtering, and sign-ups with emails) done properly from start to finish.

---

## The two pages

The test asked for two pages that let you browse events in **two clearly different
styles** — they shouldn't look like the same page twice.

- **Gallery** (the first page) — a bright grid of cards, like browsing posters on a
  wall. Each card has a picture, the date, the place, the price, and a button to
  sign up. Good for discovering events visually.
- **Agenda** (the second page) — a dark, calendar-style list where events are grouped
  by day, with a big "next up" highlight at the top. Good for planning what's coming
  in date order.

Both pages pull from the same source of event information, but they look and feel
completely different.

---

## Pictures for each event

**What was asked:** events had no pictures. Add at least two pictures per event, and
make sure the pictures are stored on our own site (not linked from somewhere else on
the internet).

**What I did:** I created a small set of attractive, colourful placeholder images —
one colour theme per event type (concerts are purple, festivals are orange, and so
on). Each event is given three of these pictures. They live on our own site, so
nothing is borrowed from another website.

**Why:** the dataset has over a million events. Creating and storing millions of
individual picture files would take up a lot of space and time for no real benefit,
since the test allows reusing the same placeholder pictures. Picking from a shared
set keeps things fast while still giving every event its own little gallery.

---

## Turning map coordinates into real place names

**What was asked:** each event only had map coordinates (a pair of numbers for
latitude and longitude). Turn those numbers into a place name a person can actually
read.

**What I did:** I built a built-in list of 75 well-known cities around the world
(their names, regions, countries, and time zones). For any event, I find the nearest
city on that list and show something like *"Austin, Texas, United States"*.

**Why:** the events were originally scattered around these same cities, so matching
each one to its nearest city is accurate. It also means the app never has to call an
outside service or the internet to look up an address — it's instant, free, and works
the same every time.

---

## Showing the right time around the world

**What was asked:** events happen all over the globe. Show the time in a sensible way.

**What I did:** every event shows the time **in its own city** (for example "2:00 PM"
in the city where it happens). On the event's detail page I also show **your** local
time next to it, plus a friendly "in 3 days" style note, so no matter where you are
you know exactly when it starts.

**Why:** a single worldwide clock would be confusing. Showing both the local-to-the-
event time and the local-to-you time is the clearest way to handle a global audience.

---

## Filtering and searching

**What was asked:** at a minimum, let people filter events **by date** and **by
location**.

**What I did:** both pages let you:

- **Filter by date** — choose a "from" and "to" date, or use the quick
  Upcoming / Past / All buttons.
- **Filter by location** — pick a city from a list.
- Plus extras: search by name, and filter by event type (concert, workshop, etc.).

By default you see **upcoming** events first, since that's what people usually want.

---

## Look and feel

**What was asked:** style everything with Tailwind (a popular styling toolkit) and add
animations where they help, without overdoing it.

**What I did:** everything is styled with Tailwind and works on phones, tablets, and
desktops. Animations are gentle and purposeful — cards lift slightly when you hover,
new items fade in as you scroll, and the sign-up form shows a friendly success
animation. Nothing flashy or distracting.

---

## Sign-ups and emails

**What was asked:** let people register their interest in an event (and keep a list of
who's coming), email them to confirm, and send reminder emails **3 days before** and
**24 hours before** the event.

**What I did:**

- **Sign-up:** anyone can register for an event by entering their name and email. They
  immediately appear on that event's attendee list.
- **No duplicates:** if the same email signs up twice for the same event, it just
  confirms they're already on the list instead of creating a second entry.
- **Confirmation email:** the moment someone signs up, they get a confirmation email.
- **Reminder emails:** the system automatically emails everyone **3 days before** and
  again **24 hours before** their event. It keeps track of what it has already sent, so
  no one ever gets the same reminder twice.

**Seeing and testing the emails:** by default the app writes emails into a log file
(`storage/logs/laravel.log`) instead of actually sending them, so you can read every
confirmation and reminder without needing a real email account. To make this easy to
check, I added two things:

- **An "Email activity & testing" page** at `/dev/emails` (when running locally) that
  shows how many confirmation and reminder emails have gone out per event, lets you
  preview the exact emails in your browser, and lists the commands to trigger them — no
  log-digging needed.
- **Hand-trigger options** on the reminder command so you can fire reminders on demand:
  - `php artisan events:send-reminders --pretend` — list who *would* be emailed.
  - `php artisan events:send-reminders --event=<id> --force` — send the reminders for one
    event right now, even if it isn't within the 3-day/24-hour window yet.
  - `php artisan events:send-reminders --window=3-day` — only one of the two windows.

  Each run prints a table of exactly who was emailed and which reminder they got.

### Why I didn't add an "approval" step

You asked whether attendees should register and then be approved or rejected by an admin.
I'd recommend **not** adding that, because:

- The test says *"let people register interest/attendance… when someone is added, email
  them to confirm they're on the list."* That describes people **joining a list
  directly** — there's no mention of an approval/gatekeeper step.
- Adding approve/reject would introduce admin accounts, permissions, and extra states
  (pending / approved / rejected) that the brief doesn't ask for — more moving parts, and
  a step further from what was requested.

Instead, I focused on making the existing flow **robust and easy to verify**: instant
sign-up, a visible attendee list, a confirmation email, automatic reminders, and the
preview/trigger tools above so you can confirm it all works. If an approval workflow is
genuinely wanted, it's a small addition on top of this — just say the word and I'll add a
"pending → approved" status with admin controls.

### Where you can see the sign-ups

- **On each event's page** there's a sign-up form and a **"Who's going" list** showing
  everyone registered, plus a running count. When you sign up, you appear in the list
  straight away.
- **On the Dashboard** there's an overview: total events, total sign-ups, the most
  recent registrations, and the most popular events.
- **An "Email activity & testing" page** (linked from the Dashboard when running
  locally) shows, per event, how many confirmation and reminder emails have been sent,
  with one-click previews of each email and copy-paste commands to trigger reminders.
- **Privacy:** the public list shows names as **first name + last initial**
  (e.g. "Ada L.") and never shows full email addresses — it hides most of each one
  (for example `ad•••@example.com`).

---

## Requirements checklist

| What the test asked for | Done? | Where to see it |
| --- | --- | --- |
| Two different page styles | ✅ | Gallery page and Agenda page |
| Title, description, location, date/time, image per event | ✅ | Any card or event page |
| Two or more pictures per event, stored on our own site | ✅ | Picture carousel on cards and event pages |
| Real place names from coordinates | ✅ | "Austin, Texas, United States" on every event |
| Sensible date/time across time zones | ✅ | Event page shows venue time **and** your time |
| Filter by date | ✅ | Date pickers + Upcoming/Past/All on both pages |
| Filter by location | ✅ | City picker on both pages |
| Styled with Tailwind | ✅ | Everywhere |
| Tasteful animations | ✅ | Hover, fade-in, success animations |
| Register interest + attendee list | ✅ | Sign-up form + "Who's going" on each event |
| Confirmation email | ✅ | Sent on sign-up (see the log file) |
| Reminders 3 days & 24 hours before | ✅ | Automatic; tracked so they never repeat |
| Clean code + notes on decisions | ✅ | This file, plus the README and the diagram |

**Nothing from the test is missing.**

---

## Working with the very large dataset

The events come pre-loaded as a big, realistic set (over a million records). I built
everything to work with it as-is and added a few behind-the-scenes speed improvements
so filtering by date and location stays fast even at that size.

For day-to-day testing on a laptop, the project loads a smaller sample (a few thousand
events) so it starts quickly — the code behaves identically either way.

---

## A picture of the database

The file **[docs/ERD.md](docs/ERD.md)** contains a simple diagram of how the
information is organised (events, the people who sign up, and the event organisers) and
confirms that no required pieces are missing.

---

## If I had more time

- Swap the colourful placeholder pictures for real photos (still stored on our own
  site).
- Add a third browsing style — an interactive map. I left this out for now because a
  map would need to load images from an outside map provider, which goes against the
  "keep everything on our own site" rule.
- Add a small admin view for organisers to see and export their attendee lists.
