<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarX2 } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import EventCard from '@/components/events/EventCard.vue';
import EventFilterBar from '@/components/events/EventFilterBar.vue';
import { useEventFeed } from '@/composables/useEventFeed';
import type { CityOption } from '@/types/events';

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
} = useEventFeed('upcoming', 24);

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
    <Head title="Discover Events" />

    <div class="mx-auto w-full max-w-7xl space-y-6 p-4 md:p-6">
        <!-- Hero -->
        <header
            class="relative overflow-hidden rounded-3xl border bg-gradient-to-br from-primary/10 via-card to-card p-6 md:p-10"
        >
            <div
                class="absolute -top-16 -right-16 size-64 rounded-full bg-primary/10 blur-3xl"
            />
            <div class="relative max-w-2xl space-y-2">
                <p
                    class="text-sm font-medium tracking-wider text-primary uppercase"
                >
                    Event Visuals · Gallery
                </p>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                    Discover what's happening
                </h1>
                <p class="text-muted-foreground">
                    Browse concerts, conferences and festivals around the world.
                    Filter by date and location, then register to get a
                    confirmation and reminders by email.
                </p>
            </div>
        </header>

        <EventFilterBar
            v-model:filters="filters"
            :cities="cities"
            :categories="categories"
            :total="meta?.total ?? null"
            @change="applyFilters"
            @reset="resetFilters"
        />

        <!-- Grid -->
        <div
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <EventCard
                v-for="(event, i) in events"
                :key="event.id"
                :event="event"
                class="animate-in duration-500 fade-in-0 fill-mode-both slide-in-from-bottom-4"
                :style="{ animationDelay: `${Math.min(i % 24, 11) * 40}ms` }"
            />

            <!-- Skeletons while loading -->
            <template v-if="loading">
                <div
                    v-for="n in events.length ? 4 : 8"
                    :key="`sk-${n}`"
                    class="overflow-hidden rounded-2xl border bg-card"
                >
                    <div class="aspect-[16/10] animate-pulse bg-muted" />
                    <div class="space-y-3 p-4">
                        <div class="h-4 w-3/4 animate-pulse rounded bg-muted" />
                        <div
                            class="h-3 w-full animate-pulse rounded bg-muted"
                        />
                        <div class="h-3 w-1/2 animate-pulse rounded bg-muted" />
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty state -->
        <div
            v-if="loaded && !loading && events.length === 0"
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed py-16 text-center"
        >
            <CalendarX2 class="size-10 text-muted-foreground" />
            <p class="font-medium">No events match your filters</p>
            <p class="text-sm text-muted-foreground">
                Try widening the date range or choosing a different location.
            </p>
        </div>

        <div ref="sentinel" class="h-4" />

        <p
            v-if="loaded && !hasMore() && events.length > 0"
            class="pb-6 text-center text-sm text-muted-foreground"
        >
            You've reached the end · {{ meta?.total?.toLocaleString() }} events
        </p>
    </div>
</template>
