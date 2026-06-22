<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarClock,
    CalendarRange,
    MapPin,
    RotateCcw,
    Search,
    Ticket,
    Users,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import EventImageCarousel from '@/components/events/EventImageCarousel.vue';
import CitySearchSelect from '@/components/events/CitySearchSelect.vue';
import RegisterInterestDialog from '@/components/events/RegisterInterestDialog.vue';
import { Button } from '@/components/ui/button';
import { useEventFeed } from '@/composables/useEventFeed';
import {
    formatDayHeading,
    formatPrice,
    relativeTime,
    statusLabel,
    venueDateParts,
    venueDayKey,
    venueTime,
} from '@/lib/eventFormat';
import type { CityOption, EventResource, WhenFilter } from '@/types/events';

defineProps<{ cities: CityOption[]; categories: string[] }>();

const {
    filters,
    events,
    meta,
    loading,
    loaded,
    loadMore,
    applyFilters,
    resetFilters,
    hasMore,
} = useEventFeed('upcoming', 20);

// The first event becomes the spotlight; the rest fill the timeline.
const featured = computed<EventResource | null>(() => events.value[0] ?? null);

const groups = computed(() => {
    const map = new Map<string, EventResource[]>();

    for (const event of events.value.slice(1)) {
        const key = venueDayKey(event);

        if (!map.has(key)) {
            map.set(key, []);
        }

        map.get(key)!.push(event);
    }

    return Array.from(map, ([date, items]) => ({ date, items }));
});

const whenOptions: { value: WhenFilter; label: string }[] = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'past', label: 'Past' },
    { value: 'all', label: 'All' },
];

const activeFilterCount = computed(() => {
    let n = 0;

    if (filters.q) {
        n++;
    }

    if (filters.city) {
        n++;
    }

    if (filters.type) {
        n++;
    }

    if (filters.from) {
        n++;
    }

    if (filters.to) {
        n++;
    }

    if (filters.when !== 'upcoming') {
        n++;
    }

    return n;
});

let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => filters.q,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 350);
    },
);

function setWhen(value: WhenFilter) {
    filters.when = value;
    applyFilters();
}

function toggleCategory(cat: string) {
    filters.type = filters.type === cat ? '' : cat;
    applyFilters();
}

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting && hasMore()) {
                loadMore();
            }
        },
        { rootMargin: '600px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events Agenda" />

    <div class="w-full space-y-8 p-4 md:p-8">
        <!-- Header -->
        <header
            class="relative overflow-hidden rounded-3xl border bg-gradient-to-br from-primary/10 via-card to-card p-6 md:p-10"
        >
            <div
                class="absolute -top-16 -right-16 size-64 rounded-full bg-primary/10 blur-3xl"
            />
            <div class="relative space-y-2">
                <span
                    class="inline-flex items-center gap-2 rounded-full border bg-background/80 px-3 py-1 text-xs font-medium tracking-wide text-primary uppercase"
                >
                    <CalendarRange class="size-3.5" /> Agenda
                </span>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                    What's coming up
                </h1>
                <p class="max-w-xl text-muted-foreground">
                    A chronological run of events. Filter by date and city to
                    plan your calendar.
                </p>
            </div>
        </header>

        <!-- Filter panel -->
        <div
            class="relative z-20 rounded-2xl border bg-card/60 p-4 shadow-sm backdrop-blur md:p-5"
        >
                <div class="grid gap-4 md:grid-cols-12">
                    <!-- Search -->
                    <div class="flex flex-col gap-1.5 md:col-span-5">
                        <label
                            for="agenda-search"
                            class="text-xs font-medium text-muted-foreground"
                            >Search</label
                        >
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <input
                                id="agenda-search"
                                v-model="filters.q"
                                placeholder="Search by event name…"
                                class="h-10 w-full rounded-xl border border-input bg-background pr-3 pl-9 text-sm placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="flex flex-col gap-1.5 md:col-span-4">
                        <label
                            for="agenda-city"
                            class="text-xs font-medium text-muted-foreground"
                            >Location</label
                        >
                        <CitySearchSelect
                            id="agenda-city"
                            v-model="filters.city"
                            :cities="cities"
                            empty-label="Anywhere in the world"
                            input-class="h-10 rounded-xl"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- When -->
                    <div class="flex flex-col gap-1.5 md:col-span-3">
                        <span class="text-xs font-medium text-muted-foreground"
                            >Timeframe</span
                        >
                        <div
                            class="inline-flex h-10 rounded-xl border bg-background p-1"
                        >
                            <button
                                v-for="opt in whenOptions"
                                :key="opt.value"
                                type="button"
                                class="flex-1 rounded-lg px-2 text-sm font-medium transition-colors"
                                :class="
                                    filters.when === opt.value
                                        ? 'bg-primary text-primary-foreground shadow'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                @click="setWhen(opt.value)"
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Date range -->
                    <div class="flex flex-col gap-1.5 md:col-span-4">
                        <label
                            for="agenda-from"
                            class="text-xs font-medium text-muted-foreground"
                            >From date</label
                        >
                        <input
                            id="agenda-from"
                            v-model="filters.from"
                            type="date"
                            class="h-10 rounded-xl border border-input bg-background px-3 text-sm focus:border-ring focus:ring-2 focus:ring-ring/30 focus:outline-none"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-4">
                        <label
                            for="agenda-to"
                            class="text-xs font-medium text-muted-foreground"
                            >To date</label
                        >
                        <input
                            id="agenda-to"
                            v-model="filters.to"
                            type="date"
                            class="h-10 rounded-xl border border-input bg-background px-3 text-sm focus:border-ring focus:ring-2 focus:ring-ring/30 focus:outline-none"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Category -->
                    <div class="flex flex-col gap-1.5 md:col-span-4">
                        <span class="text-xs font-medium text-muted-foreground"
                            >Category</span
                        >
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="cat in categories"
                                :key="cat"
                                type="button"
                                class="rounded-full border px-2.5 py-1 text-xs font-medium capitalize transition-colors"
                                :class="
                                    filters.type === cat
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'hover:border-primary/50 hover:bg-accent'
                                "
                                @click="toggleCategory(cat)"
                            >
                                {{ cat }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Result summary + clear -->
                <div
                    class="mt-4 flex items-center justify-between border-t pt-3 text-sm"
                >
                    <p class="text-muted-foreground">
                        <span class="font-semibold text-foreground">{{
                            (meta?.total ?? 0).toLocaleString()
                        }}</span>
                        {{ (meta?.total ?? 0) === 1 ? 'event' : 'events' }}
                        found
                    </p>
                    <button
                        v-if="activeFilterCount > 0"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        @click="resetFilters"
                    >
                        <RotateCcw class="size-3.5" /> Clear
                        {{ activeFilterCount }} filter{{
                            activeFilterCount === 1 ? '' : 's'
                        }}
                    </button>
                </div>
            </div>

            <!-- Spotlight -->
            <Link
                v-if="featured"
                :href="`/events/${featured.id}?from=visual2`"
                class="group relative block overflow-hidden rounded-3xl border shadow-lg"
            >
                <div class="aspect-[2/1] w-full sm:aspect-[21/8]">
                    <EventImageCarousel
                        :images="featured.images"
                        :alt="featured.name"
                    />
                </div>
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-t from-background via-background/50 to-transparent"
                />
                <div class="absolute inset-x-0 bottom-0 space-y-2.5 p-5 md:p-8">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground shadow-lg"
                    >
                        <Ticket class="size-3.5" /> Next up ·
                        {{ relativeTime(featured.time.starts_at_utc) }}
                    </span>
                    <h2 class="text-2xl font-bold tracking-tight md:text-4xl">
                        {{ featured.name }}
                    </h2>
                    <div
                        class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1.5"
                            ><CalendarClock class="size-4 text-primary" />
                            {{ venueTime(featured) }}</span
                        >
                        <span class="inline-flex items-center gap-1.5"
                            ><MapPin class="size-4 text-primary" />
                            {{ featured.location.label }}</span
                        >
                        <span class="inline-flex items-center gap-1.5"
                            ><Users class="size-4 text-primary" />
                            {{ featured.attendees_count }} going</span
                        >
                        <span
                            class="ml-auto text-base font-semibold text-foreground"
                            >{{ formatPrice(featured) }}</span
                        >
                    </div>
                </div>
            </Link>

            <!-- Timeline -->
            <div v-if="groups.length > 0" class="relative">
                <!-- Continuous rail -->
                <span
                    class="pointer-events-none absolute top-2 bottom-2 left-5 w-px bg-gradient-to-b from-primary/50 via-border to-transparent"
                />

                <div class="space-y-10">
                    <section
                        v-for="group in groups"
                        :key="group.date"
                        class="relative pl-[60px]"
                    >
                        <!-- Date node -->
                        <div
                            class="absolute top-0 left-0 z-10 grid size-10 place-items-center rounded-2xl border bg-card text-center shadow-sm"
                        >
                            <div class="leading-none">
                                <div
                                    class="text-[9px] font-semibold tracking-wide text-primary uppercase"
                                >
                                    {{ venueDateParts(group.items[0]).month }}
                                </div>
                                <div class="text-base font-bold">
                                    {{ venueDateParts(group.items[0]).day }}
                                </div>
                            </div>
                        </div>

                        <h3 class="pt-1.5 text-sm font-semibold">
                            {{ formatDayHeading(group.date) }}
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            {{ group.items.length }} event{{
                                group.items.length === 1 ? '' : 's'
                            }}
                        </p>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="event in group.items"
                                :key="event.id"
                                class="group flex animate-in flex-col gap-4 rounded-2xl border bg-card p-3 transition-all duration-300 fade-in-0 fill-mode-both slide-in-from-left-2 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md sm:flex-row"
                            >
                                <Link
                                    :href="`/events/${event.id}?from=visual2`"
                                    class="h-36 w-full shrink-0 overflow-hidden rounded-xl sm:h-24 sm:w-32"
                                >
                                    <EventImageCarousel
                                        :images="[event.images[0]]"
                                        :alt="event.name"
                                    />
                                </Link>

                                <div
                                    class="flex min-w-0 flex-1 flex-col gap-1.5"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-medium tracking-wide capitalize"
                                            >{{ event.type }}</span
                                        >
                                        <span
                                            v-if="event.status !== 'published'"
                                            class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-medium text-amber-700 capitalize dark:text-amber-300"
                                        >
                                            {{ statusLabel(event.status) }}
                                        </span>
                                    </div>
                                    <Link :href="`/events/${event.id}?from=visual2`">
                                        <h4
                                            class="truncate font-semibold transition-colors group-hover:text-primary"
                                        >
                                            {{ event.name }}
                                        </h4>
                                    </Link>
                                    <div
                                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                            ><CalendarClock class="size-3.5" />
                                            {{ event.time.starts_at_label }}
                                            {{
                                                event.time.tz_abbreviation
                                            }}</span
                                        >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                            ><MapPin class="size-3.5" />
                                            {{ event.location.city }},
                                            {{ event.location.country }}</span
                                        >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                            ><Users class="size-3.5" />
                                            {{ event.attendees_count }}
                                            going</span
                                        >
                                    </div>

                                    <div
                                        class="mt-2 flex items-center justify-between gap-3 border-t pt-2 sm:mt-auto"
                                    >
                                        <span
                                            class="text-sm font-semibold"
                                            >{{ formatPrice(event) }}</span
                                        >
                                        <RegisterInterestDialog :event="event">
                                            <Button size="sm"
                                                >Register interest</Button
                                            >
                                        </RegisterInterestDialog>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="space-y-3">
                <div
                    v-for="n in 4"
                    :key="`sk-${n}`"
                    class="h-28 animate-pulse rounded-2xl border bg-muted"
                />
            </div>

            <!-- Empty -->
            <div
                v-if="loaded && !loading && events.length === 0"
                class="rounded-2xl border border-dashed py-16 text-center"
            >
                <CalendarRange class="mx-auto size-10 text-muted-foreground" />
                <p class="mt-3 font-medium">Nothing on the agenda</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Try a different date range or city.
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    class="mt-4"
                    @click="resetFilters"
                >
                    Clear filters
                </Button>
            </div>

            <div ref="sentinel" class="h-4" />
            <p
                v-if="loaded && !hasMore() && events.length > 0"
                class="pb-6 text-center text-sm text-muted-foreground"
            >
                End of the agenda · {{ meta?.total?.toLocaleString() }} events
            </p>
    </div>
</template>
