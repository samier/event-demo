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

    <div
        class="relative min-h-full overflow-hidden bg-slate-950 text-slate-100"
    >
        <!-- Ambient background -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-40 left-1/4 size-[32rem] rounded-full bg-indigo-600/20 blur-[130px]"
            />
            <div
                class="absolute top-1/3 -right-20 size-[28rem] rounded-full bg-fuchsia-600/10 blur-[130px]"
            />
        </div>

        <div class="relative mx-auto w-full max-w-6xl space-y-8 p-4 md:p-8">
            <!-- Header -->
            <header class="space-y-2">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium tracking-wide text-indigo-300 uppercase"
                >
                    <CalendarRange class="size-3.5" /> Agenda
                </span>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                    What's coming up
                </h1>
                <p class="max-w-xl text-slate-400">
                    A chronological run of events. Filter by date and city to
                    plan your calendar.
                </p>
            </header>

            <!-- Filter panel (prominent + labelled) -->
            <div
                class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 shadow-xl shadow-black/20 backdrop-blur-xl md:p-5"
            >
                <div class="grid gap-4 md:grid-cols-12">
                    <!-- Search -->
                    <div class="flex flex-col gap-1.5 md:col-span-5">
                        <label
                            for="agenda-search"
                            class="text-xs font-medium text-slate-400"
                            >Search</label
                        >
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500"
                            />
                            <input
                                id="agenda-search"
                                v-model="filters.q"
                                placeholder="Search by event name…"
                                class="h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 pr-3 pl-9 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="flex flex-col gap-1.5 md:col-span-4">
                        <label
                            for="agenda-city"
                            class="text-xs font-medium text-slate-400"
                            >Location</label
                        >
                        <select
                            id="agenda-city"
                            v-model="filters.city"
                            class="h-10 rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm text-slate-100 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                            @change="applyFilters"
                        >
                            <option value="">Anywhere in the world</option>
                            <option
                                v-for="c in cities"
                                :key="`${c.city}-${c.country_code}`"
                                :value="c.city"
                            >
                                {{ c.city }}, {{ c.country }}
                            </option>
                        </select>
                    </div>

                    <!-- When -->
                    <div class="flex flex-col gap-1.5 md:col-span-3">
                        <span class="text-xs font-medium text-slate-400"
                            >Timeframe</span
                        >
                        <div
                            class="inline-flex h-10 rounded-xl border border-white/10 bg-slate-950/60 p-1"
                        >
                            <button
                                v-for="opt in whenOptions"
                                :key="opt.value"
                                type="button"
                                class="flex-1 rounded-lg px-2 text-sm font-medium transition-colors"
                                :class="
                                    filters.when === opt.value
                                        ? 'bg-indigo-500 text-white shadow'
                                        : 'text-slate-400 hover:text-slate-100'
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
                            class="text-xs font-medium text-slate-400"
                            >From date</label
                        >
                        <input
                            id="agenda-from"
                            v-model="filters.from"
                            type="date"
                            class="h-10 rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm text-slate-100 [color-scheme:dark] focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex flex-col gap-1.5 md:col-span-4">
                        <label
                            for="agenda-to"
                            class="text-xs font-medium text-slate-400"
                            >To date</label
                        >
                        <input
                            id="agenda-to"
                            v-model="filters.to"
                            type="date"
                            class="h-10 rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm text-slate-100 [color-scheme:dark] focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/30 focus:outline-none"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Category -->
                    <div class="flex flex-col gap-1.5 md:col-span-4">
                        <span class="text-xs font-medium text-slate-400"
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
                                        ? 'border-indigo-400 bg-indigo-500 text-white'
                                        : 'border-white/10 text-slate-400 hover:border-white/30 hover:text-slate-200'
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
                    class="mt-4 flex items-center justify-between border-t border-white/10 pt-3 text-sm"
                >
                    <p class="text-slate-400">
                        <span class="font-semibold text-slate-200">{{
                            (meta?.total ?? 0).toLocaleString()
                        }}</span>
                        {{ (meta?.total ?? 0) === 1 ? 'event' : 'events' }}
                        found
                    </p>
                    <button
                        v-if="activeFilterCount > 0"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-slate-400 transition-colors hover:bg-white/5 hover:text-slate-100"
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
                :href="`/events/${featured.id}`"
                class="group relative block overflow-hidden rounded-3xl border border-white/10 shadow-2xl shadow-black/40"
            >
                <div class="aspect-[2/1] w-full sm:aspect-[21/8]">
                    <EventImageCarousel
                        :images="featured.images"
                        :alt="featured.name"
                    />
                </div>
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/50 to-transparent"
                />
                <div class="absolute inset-x-0 bottom-0 space-y-2.5 p-5 md:p-8">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-indigo-500 px-3 py-1 text-xs font-semibold text-white shadow-lg"
                    >
                        <Ticket class="size-3.5" /> Next up ·
                        {{ relativeTime(featured.time.starts_at_utc) }}
                    </span>
                    <h2 class="text-2xl font-bold tracking-tight md:text-4xl">
                        {{ featured.name }}
                    </h2>
                    <div
                        class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm text-slate-200"
                    >
                        <span class="inline-flex items-center gap-1.5"
                            ><CalendarClock class="size-4 text-indigo-300" />
                            {{ venueTime(featured) }}</span
                        >
                        <span class="inline-flex items-center gap-1.5"
                            ><MapPin class="size-4 text-indigo-300" />
                            {{ featured.location.label }}</span
                        >
                        <span class="inline-flex items-center gap-1.5"
                            ><Users class="size-4 text-indigo-300" />
                            {{ featured.attendees_count }} going</span
                        >
                        <span
                            class="ml-auto text-base font-semibold text-white"
                            >{{ formatPrice(featured) }}</span
                        >
                    </div>
                </div>
            </Link>

            <!-- Timeline -->
            <div v-if="groups.length > 0" class="relative">
                <!-- Continuous rail -->
                <span
                    class="pointer-events-none absolute top-2 bottom-2 left-5 w-px bg-gradient-to-b from-indigo-500/50 via-white/10 to-transparent"
                />

                <div class="space-y-10">
                    <section
                        v-for="group in groups"
                        :key="group.date"
                        class="relative pl-[60px]"
                    >
                        <!-- Date node -->
                        <div
                            class="absolute top-0 left-0 z-10 grid size-10 place-items-center rounded-2xl border border-white/10 bg-slate-900 text-center shadow-lg"
                        >
                            <div class="leading-none">
                                <div
                                    class="text-[9px] font-semibold tracking-wide text-indigo-400 uppercase"
                                >
                                    {{ venueDateParts(group.items[0]).month }}
                                </div>
                                <div class="text-base font-bold">
                                    {{ venueDateParts(group.items[0]).day }}
                                </div>
                            </div>
                        </div>

                        <h3 class="pt-1.5 text-sm font-semibold text-slate-300">
                            {{ formatDayHeading(group.date) }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            {{ group.items.length }} event{{
                                group.items.length === 1 ? '' : 's'
                            }}
                        </p>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="event in group.items"
                                :key="event.id"
                                class="group flex animate-in flex-col gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-3 transition-all duration-300 fade-in-0 fill-mode-both slide-in-from-left-2 hover:-translate-y-0.5 hover:border-indigo-400/40 hover:bg-white/[0.06] hover:shadow-lg hover:shadow-indigo-500/5 sm:flex-row"
                            >
                                <Link
                                    :href="`/events/${event.id}`"
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
                                            class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium tracking-wide text-slate-200 capitalize"
                                            >{{ event.type }}</span
                                        >
                                        <span
                                            v-if="event.status !== 'published'"
                                            class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-medium text-amber-300 capitalize"
                                        >
                                            {{ statusLabel(event.status) }}
                                        </span>
                                    </div>
                                    <Link :href="`/events/${event.id}`">
                                        <h4
                                            class="truncate font-semibold transition-colors group-hover:text-indigo-300"
                                        >
                                            {{ event.name }}
                                        </h4>
                                    </Link>
                                    <div
                                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400"
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
                                        class="mt-2 flex items-center justify-between gap-3 border-t border-white/5 pt-2 sm:mt-auto"
                                    >
                                        <span
                                            class="text-sm font-semibold text-white"
                                            >{{ formatPrice(event) }}</span
                                        >
                                        <RegisterInterestDialog :event="event">
                                            <Button
                                                size="sm"
                                                class="bg-indigo-500 text-white hover:bg-indigo-400"
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
                    class="h-28 animate-pulse rounded-2xl border border-white/10 bg-white/[0.03]"
                />
            </div>

            <!-- Empty -->
            <div
                v-if="loaded && !loading && events.length === 0"
                class="rounded-2xl border border-dashed border-white/15 py-16 text-center"
            >
                <CalendarRange class="mx-auto size-10 text-slate-600" />
                <p class="mt-3 font-medium">Nothing on the agenda</p>
                <p class="mt-1 text-sm text-slate-500">
                    Try a different date range or city.
                </p>
                <Button
                    variant="outline"
                    size="sm"
                    class="mt-4 border-white/15 bg-transparent text-slate-200 hover:bg-white/5"
                    @click="resetFilters"
                >
                    Clear filters
                </Button>
            </div>

            <div ref="sentinel" class="h-4" />
            <p
                v-if="loaded && !hasMore() && events.length > 0"
                class="pb-6 text-center text-sm text-slate-500"
            >
                End of the agenda · {{ meta?.total?.toLocaleString() }} events
            </p>
        </div>
    </div>
</template>
