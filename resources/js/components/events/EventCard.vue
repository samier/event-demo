<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import EventImageCarousel from '@/components/events/EventImageCarousel.vue';
import RegisterInterestDialog from '@/components/events/RegisterInterestDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatPrice,
    relativeTime,
    statusLabel,
    venueTime,
} from '@/lib/eventFormat';
import type { EventResource } from '@/types/events';

const props = defineProps<{ event: EventResource; from?: string }>();

const eventUrl = computed(
    () =>
        `/events/${props.event.id}${props.from ? `?from=${props.from}` : ''}`,
);

const attendees = ref(props.event.attendees_count);

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
    <article
        class="group flex flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5"
    >
        <Link
            :href="eventUrl"
            class="relative block aspect-[16/10] overflow-hidden"
        >
            <EventImageCarousel :images="event.images" :alt="event.name" />
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"
            />
            <div class="absolute top-3 left-3 flex gap-2">
                <Badge class="capitalize backdrop-blur">{{ event.type }}</Badge>
                <Badge
                    v-if="event.status !== 'published'"
                    :variant="statusVariant"
                    class="capitalize backdrop-blur"
                >
                    {{ statusLabel(event.status) }}
                </Badge>
            </div>
            <div
                class="absolute right-3 bottom-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-foreground shadow dark:bg-black/70"
            >
                {{ formatPrice(event) }}
            </div>
        </Link>

        <div class="flex flex-1 flex-col gap-3 p-4">
            <div class="space-y-1">
                <Link :href="eventUrl">
                    <h3
                        class="line-clamp-1 leading-tight font-semibold transition-colors group-hover:text-primary"
                    >
                        {{ event.name }}
                    </h3>
                </Link>
                <p class="line-clamp-2 text-sm text-muted-foreground">
                    {{ event.description }}
                </p>
            </div>

            <div class="mt-auto space-y-1.5 text-sm">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <CalendarDays class="size-4 shrink-0" />
                    <span class="truncate">{{ venueTime(event) }}</span>
                    <span
                        class="ml-auto shrink-0 text-xs font-medium text-primary"
                        >{{ relativeTime(event.time.starts_at_utc) }}</span
                    >
                </div>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <MapPin class="size-4 shrink-0" />
                    <span class="truncate">{{ event.location.label }}</span>
                </div>
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Users class="size-4 shrink-0" />
                    <span
                        >{{ attendees }}
                        {{ attendees === 1 ? 'person' : 'people' }} going</span
                    >
                </div>
            </div>

            <div class="flex gap-2 pt-1">
                <RegisterInterestDialog
                    :event="event"
                    class="flex-1"
                    @registered="(c) => (attendees = c)"
                >
                    <Button class="w-full" size="sm">Register interest</Button>
                </RegisterInterestDialog>
                <Button as-child variant="outline" size="sm">
                    <Link :href="eventUrl">Details</Link>
                </Button>
            </div>
        </div>
    </article>
</template>
