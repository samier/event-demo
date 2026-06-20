<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarClock,
    Clock,
    Globe,
    MapPin,
    Ticket,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AttendeeList from '@/components/events/AttendeeList.vue';
import EventImageCarousel from '@/components/events/EventImageCarousel.vue';
import RegisterInterestForm from '@/components/events/RegisterInterestForm.vue';
import { Badge } from '@/components/ui/badge';
import {
    formatPrice,
    relativeTime,
    statusLabel,
    venueTime,
} from '@/lib/eventFormat';
import type {
    Attendee,
    EventResource,
    RegistrationResult,
} from '@/types/events';

const props = defineProps<{ event: EventResource; attendees: Attendee[] }>();

const attendees = ref<Attendee[]>([...props.attendees]);
const attendeesCount = ref(props.event.attendees_count);

function onRegistered(result: RegistrationResult) {
    attendeesCount.value = result.attendees_count;

    // Add the new attendee to the top of the list (duplicates are blocked server-side).
    if (!attendees.value.some((a) => a.id === result.attendee.id)) {
        attendees.value.unshift(result.attendee);
    }
}

// Show the event in the viewer's own timezone too, so a global audience always
// knows when it starts relative to them.
const viewerTime = computed(() =>
    new Date(props.event.time.starts_at_utc).toLocaleString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZoneName: 'short',
    }),
);

const mapUrl = computed(() => {
    const { latitude, longitude } = props.event.location;

    return `https://www.openstreetmap.org/?mlat=${latitude}&mlon=${longitude}#map=13/${latitude}/${longitude}`;
});

const statusVariant = computed(() => {
    switch (props.event.status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Head :title="event.name" />

    <div class="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
        <Link
            href="/events-visual-1"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to events
        </Link>

        <!-- Gallery -->
        <div class="aspect-[21/9] overflow-hidden rounded-3xl border">
            <EventImageCarousel :images="event.images" :alt="event.name" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <!-- Main -->
            <div class="space-y-6">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge class="capitalize">{{ event.type }}</Badge>
                        <Badge :variant="statusVariant" class="capitalize">{{
                            statusLabel(event.status)
                        }}</Badge>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ event.name }}
                    </h1>
                    <p
                        v-if="event.organizer"
                        class="text-sm text-muted-foreground"
                    >
                        Hosted by {{ event.organizer }}
                    </p>
                </div>

                <p class="leading-relaxed text-muted-foreground">
                    {{ event.description }}
                </p>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex items-start gap-3 rounded-xl border p-4">
                        <CalendarClock class="mt-0.5 size-5 text-primary" />
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Local time at venue
                            </p>
                            <p class="font-medium">{{ venueTime(event) }}</p>
                            <p class="text-xs text-primary">
                                {{ relativeTime(event.time.starts_at_utc) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-xl border p-4">
                        <Clock class="mt-0.5 size-5 text-primary" />
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Your local time
                            </p>
                            <p class="font-medium">{{ viewerTime }}</p>
                            <p class="text-xs text-muted-foreground">
                                Venue timezone: {{ event.time.timezone }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-xl border p-4">
                        <MapPin class="mt-0.5 size-5 text-primary" />
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Location
                            </p>
                            <p class="font-medium">{{ event.venue.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ event.location.label }}
                            </p>
                            <a
                                :href="mapUrl"
                                target="_blank"
                                rel="noopener"
                                class="mt-1 inline-flex items-center gap-1 text-xs text-primary hover:underline"
                            >
                                <Globe class="size-3" /> View on map
                            </a>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-xl border p-4">
                        <Users class="mt-0.5 size-5 text-primary" />
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Attendance
                            </p>
                            <p class="font-medium">
                                {{ attendeesCount.toLocaleString() }} registered
                            </p>
                            <p
                                v-if="event.venue.capacity"
                                class="text-sm text-muted-foreground"
                            >
                                Capacity
                                {{ event.venue.capacity.toLocaleString() }}
                            </p>
                        </div>
                    </div>
                </div>

                <AttendeeList :attendees="attendees" :total="attendeesCount" />
            </div>

            <!-- Sticky register panel -->
            <aside class="lg:sticky lg:top-6 lg:h-fit">
                <div class="space-y-5 rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">From</p>
                            <p class="text-3xl font-bold">
                                {{ formatPrice(event) }}
                            </p>
                        </div>
                        <span
                            class="flex items-center gap-1.5 text-sm text-muted-foreground"
                        >
                            <Ticket class="size-4" />
                            {{ attendeesCount.toLocaleString() }} going
                        </span>
                    </div>

                    <div class="border-t pt-5">
                        <h2 class="mb-3 font-semibold">
                            Register your interest
                        </h2>
                        <RegisterInterestForm
                            :event="event"
                            id-prefix="detail"
                            @registered="onRegistered"
                        />
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
