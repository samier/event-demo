import type { EventResource } from '@/types/events';

/**
 * Relative time from now, e.g. "in 3 days", "tomorrow", "in 2 hours", "2 days ago".
 * Uses the canonical UTC instant so it is correct regardless of the viewer's locale.
 */
export function relativeTime(iso: string): string {
    const target = new Date(iso).getTime();
    const diffMs = target - Date.now();
    const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });
    const abs = Math.abs(diffMs);

    const minute = 60_000;
    const hour = 60 * minute;
    const day = 24 * hour;

    if (abs < hour) {
        return rtf.format(Math.round(diffMs / minute), 'minute');
    }

    if (abs < day) {
        return rtf.format(Math.round(diffMs / hour), 'hour');
    }

    if (abs < 30 * day) {
        return rtf.format(Math.round(diffMs / day), 'day');
    }

    if (abs < 365 * day) {
        return rtf.format(Math.round(diffMs / (30 * day)), 'month');
    }

    return rtf.format(Math.round(diffMs / (365 * day)), 'year');
}

export function isUpcoming(event: EventResource): boolean {
    return new Date(event.time.starts_at_utc).getTime() > Date.now();
}

/** Venue-local time label as computed server-side, e.g. "Sat, Jun 21, 2026 · 8:00 PM". */
export function venueTime(event: EventResource): string {
    return `${event.time.starts_at_label} ${event.time.tz_abbreviation}`;
}

/** Day-of-month / month / weekday split, in the venue's timezone, for calendar chips. */
export function venueDateParts(event: EventResource): {
    day: string;
    month: string;
    weekday: string;
} {
    // Use the venue-local *calendar date* (YYYY-MM-DD) at UTC midnight so the chip
    // matches the day header and row time. Parsing the offset-aware instant and
    // reading UTC parts would roll past midnight for late-evening local times.
    const d = new Date(`${venueDayKey(event)}T00:00:00Z`);

    return {
        day: String(d.getUTCDate()),
        month: d.toLocaleString('en-US', { month: 'short', timeZone: 'UTC' }),
        weekday: d.toLocaleString('en-US', {
            weekday: 'short',
            timeZone: 'UTC',
        }),
    };
}

/** Group key (year-month-day) in the venue's timezone for the agenda view. */
export function venueDayKey(event: EventResource): string {
    return event.time.starts_at_local.slice(0, 10);
}

export function formatDayHeading(isoDate: string): string {
    const d = new Date(`${isoDate}T00:00:00Z`);

    return d.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'UTC',
    });
}

export function formatPrice(event: EventResource): string {
    const price = event.pricing.min_price;

    if (price === null) {
        return '—';
    }

    if (price === 0) {
        return 'Free';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: event.pricing.currency || 'USD',
        maximumFractionDigits: 0,
    }).format(price);
}

export function statusLabel(status: string): string {
    return status.replace('_', ' ');
}
