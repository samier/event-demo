<script setup lang="ts">
import { Users } from '@lucide/vue';
import { computed } from 'vue';
import { relativeTime } from '@/lib/eventFormat';
import type { Attendee } from '@/types/events';

const props = defineProps<{
    attendees: Attendee[];
    total: number;
}>();

// A small deterministic palette so avatars aren't all the same colour.
const palette = [
    'bg-rose-500',
    'bg-amber-500',
    'bg-emerald-500',
    'bg-sky-500',
    'bg-violet-500',
    'bg-fuchsia-500',
    'bg-teal-500',
    'bg-indigo-500',
];

function colour(attendee: Attendee): string {
    const sum = attendee.name
        .split('')
        .reduce((acc, c) => acc + c.charCodeAt(0), 0);

    return palette[sum % palette.length];
}

const remaining = computed(() =>
    Math.max(0, props.total - props.attendees.length),
);
</script>

<template>
    <section class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm">
        <header class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 font-semibold">
                <Users class="size-4 text-primary" />
                Who's going
            </h2>
            <span
                class="rounded-full bg-muted px-2.5 py-0.5 text-sm font-medium text-muted-foreground"
            >
                {{ total.toLocaleString() }}
            </span>
        </header>

        <!-- Empty state -->
        <div
            v-if="attendees.length === 0"
            class="rounded-xl border border-dashed py-8 text-center"
        >
            <p class="text-sm font-medium">No one has registered yet</p>
            <p class="mt-1 text-xs text-muted-foreground">
                Be the first to join the attendee list.
            </p>
        </div>

        <!-- List -->
        <ul v-else class="divide-y">
            <li
                v-for="attendee in attendees"
                :key="attendee.id"
                class="flex animate-in items-center gap-3 py-2.5 duration-300 fade-in-0 fill-mode-both slide-in-from-bottom-1"
            >
                <span
                    class="grid size-9 shrink-0 place-items-center rounded-full text-xs font-semibold text-white"
                    :class="colour(attendee)"
                    aria-hidden="true"
                >
                    {{ attendee.initials }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ attendee.name }}
                    </p>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ attendee.email_masked }}
                    </p>
                </div>
                <span
                    v-if="attendee.registered_at"
                    class="shrink-0 text-xs text-muted-foreground"
                >
                    {{ relativeTime(attendee.registered_at) }}
                </span>
            </li>
        </ul>

        <p
            v-if="remaining > 0"
            class="text-center text-xs text-muted-foreground"
        >
            + {{ remaining.toLocaleString() }} more
        </p>
    </section>
</template>
