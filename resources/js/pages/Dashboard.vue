<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    Mail,
    TicketCheck,
    TrendingUp,
    UserPlus,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { relativeTime } from '@/lib/eventFormat';
import { dashboard } from '@/routes';

const isLocal = computed(() => Boolean(usePage().props.isLocal));

interface RecentRegistration {
    id: number;
    name: string;
    event_id: string;
    event_name: string;
    registered_at: string | null;
}

interface TopEvent {
    id: string;
    name: string;
    type: string;
    attendees_count: number;
}

const props = defineProps<{
    stats: {
        total_events: number;
        total_attendees: number;
        events_with_attendees: number;
    };
    recentRegistrations: RecentRegistration[];
    topEvents: TopEvent[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const cards = [
    {
        label: 'Total events',
        value: props.stats.total_events,
        icon: CalendarDays,
    },
    {
        label: 'Total attendees',
        value: props.stats.total_attendees,
        icon: Users,
    },
    {
        label: 'Events with sign-ups',
        value: props.stats.events_with_attendees,
        icon: TicketCheck,
    },
];

function initials(name: string): string {
    const parts = name.trim().split(/\s+/);

    return (
        (
            (parts[0]?.[0] ?? '') +
            (parts.length > 1 ? (parts[parts.length - 1][0] ?? '') : '')
        ).toUpperCase() || '?'
    );
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="text-sm text-muted-foreground">
                    An overview of events and attendee registrations.
                </p>
            </div>
            <Link
                v-if="isLocal"
                href="/dev/emails"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
            >
                <Mail class="size-4 text-primary" /> Email activity &amp;
                testing
            </Link>
        </div>

        <!-- Stat cards -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="card in cards"
                :key="card.label"
                class="flex items-center gap-4 rounded-2xl border bg-card p-5 shadow-sm"
            >
                <div
                    class="grid size-11 place-items-center rounded-xl bg-primary/10 text-primary"
                >
                    <component :is="card.icon" class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold">
                        {{ card.value.toLocaleString() }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ card.label }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Recent registrations -->
            <section class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="flex items-center gap-2 font-semibold">
                    <UserPlus class="size-4 text-primary" /> Recent
                    registrations
                </h2>

                <div
                    v-if="recentRegistrations.length === 0"
                    class="rounded-xl border border-dashed py-10 text-center"
                >
                    <p class="text-sm font-medium">No registrations yet</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Register for an event from the
                        <Link
                            href="/events-visual-1"
                            class="text-primary hover:underline"
                            >Gallery</Link
                        >
                        to see it here.
                    </p>
                </div>

                <ul v-else class="divide-y">
                    <li
                        v-for="reg in recentRegistrations"
                        :key="reg.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <span
                            class="grid size-9 shrink-0 place-items-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                        >
                            {{ initials(reg.name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ reg.name }}
                            </p>
                            <Link
                                :href="`/events/${reg.event_id}`"
                                class="truncate text-xs text-muted-foreground hover:text-primary"
                            >
                                {{ reg.event_name }}
                            </Link>
                        </div>
                        <span
                            v-if="reg.registered_at"
                            class="shrink-0 text-xs text-muted-foreground"
                        >
                            {{ relativeTime(reg.registered_at) }}
                        </span>
                    </li>
                </ul>
            </section>

            <!-- Top events -->
            <section class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="flex items-center gap-2 font-semibold">
                    <TrendingUp class="size-4 text-primary" /> Most popular
                    events
                </h2>

                <div
                    v-if="topEvents.length === 0"
                    class="rounded-xl border border-dashed py-10 text-center"
                >
                    <p class="text-sm font-medium">Nothing to rank yet</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Popular events appear here once people register.
                    </p>
                </div>

                <ul v-else class="space-y-2">
                    <li v-for="(ev, i) in topEvents" :key="ev.id">
                        <Link
                            :href="`/events/${ev.id}`"
                            class="flex items-center gap-3 rounded-xl border p-3 transition-colors hover:bg-accent"
                        >
                            <span
                                class="grid size-7 shrink-0 place-items-center rounded-full bg-muted text-xs font-bold text-muted-foreground"
                            >
                                {{ i + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ ev.name }}
                                </p>
                                <p
                                    class="text-xs text-muted-foreground capitalize"
                                >
                                    {{ ev.type }}
                                </p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold">{{
                                ev.attendees_count.toLocaleString()
                            }}</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
