export interface EventLocation {
    city: string;
    region: string;
    country: string;
    country_code: string;
    label: string;
    latitude: number | null;
    longitude: number | null;
}

export interface EventTime {
    timezone: string;
    starts_at_utc: string;
    ends_at_utc: string | null;
    starts_at_local: string;
    starts_at_label: string;
    tz_abbreviation: string;
}

export interface EventResource {
    id: string;
    name: string;
    description: string | null;
    type: string;
    status: string;
    images: string[];
    venue: { name: string | null; capacity: number | null };
    organizer: string | null;
    location: EventLocation;
    time: EventTime;
    pricing: { currency: string; min_price: number | null };
    attendees_count: number;
}

export interface Attendee {
    id: number;
    name: string;
    initials: string;
    email_masked: string;
    registered_at: string | null;
}

export interface RegistrationResult {
    message: string;
    attendee: Attendee;
    attendees_count: number;
    errors?: Record<string, string[]>;
}

export interface CityOption {
    city: string;
    country: string;
    country_code: string;
    lat: number;
    lng: number;
}

export interface FeedMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

export type WhenFilter = 'upcoming' | 'past' | 'all';

export interface EventFilters {
    q: string;
    city: string;
    type: string;
    from: string;
    to: string;
    when: WhenFilter;
}
