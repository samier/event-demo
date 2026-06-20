<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, Copy, Mail, MailCheck, Send, Users } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { relativeTime } from '@/lib/eventFormat';

interface ActivityEvent {
    id: string;
    name: string;
    location: string;
    starts_at_utc: string;
    starts_at_label: string;
    attendees_count: number;
    confirmed_count: number;
    reminded_3d_count: number;
    reminded_24h_count: number;
}

const props = defineProps<{
    totals: {
        attendees: number;
        confirmed: number;
        reminded_3d: number;
        reminded_24h: number;
    };
    events: ActivityEvent[];
}>();

const cards = [
    { label: 'Total sign-ups', value: props.totals.attendees, icon: Users },
    {
        label: 'Confirmation emails sent',
        value: props.totals.confirmed,
        icon: MailCheck,
    },
    {
        label: '3-day reminders sent',
        value: props.totals.reminded_3d,
        icon: Send,
    },
    {
        label: '24-hour reminders sent',
        value: props.totals.reminded_24h,
        icon: Send,
    },
];

const commands = [
    {
        label: 'Preview who is due a reminder (no emails sent)',
        cmd: 'php artisan events:send-reminders --pretend',
    },
    {
        label: 'Send reminders that are currently due',
        cmd: 'php artisan events:send-reminders',
    },
    {
        label: 'Force-send both reminders for one event now',
        cmd: 'php artisan events:send-reminders --event=<EVENT_ID> --force',
    },
];

const copied = ref<string | null>(null);
function copy(cmd: string) {
    navigator.clipboard?.writeText(cmd);
    copied.value = cmd;
    window.setTimeout(
        () => (copied.value = copied.value === cmd ? null : copied.value),
        1500,
    );
}
</script>

<template>
    <Head title="Email activity" />

    <div class="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
        <div>
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400"
            >
                Local testing tool
            </span>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">
                Email activity &amp; testing
            </h1>
            <p class="text-sm text-muted-foreground">
                Verify the confirmation and reminder emails without waiting for
                the event dates. Emails are written to
                <code class="rounded bg-muted px-1 py-0.5 text-xs"
                    >storage/logs/laravel.log</code
                >
                by default.
            </p>
        </div>

        <!-- Stat cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.label"
                class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
            >
                <div
                    class="grid size-10 place-items-center rounded-xl bg-primary/10 text-primary"
                >
                    <component :is="card.icon" class="size-5" />
                </div>
                <div>
                    <p class="text-xl font-bold">
                        {{ card.value.toLocaleString() }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ card.label }}
                    </p>
                </div>
            </div>
        </div>

        <!-- How to test -->
        <section class="space-y-3 rounded-2xl border bg-card p-5 shadow-sm">
            <h2 class="font-semibold">Trigger reminders manually</h2>
            <p class="text-sm text-muted-foreground">
                Reminders normally fire automatically (hourly) 3 days and 24
                hours before an event. To test on demand, run:
            </p>
            <div class="space-y-2">
                <div v-for="c in commands" :key="c.cmd" class="space-y-1">
                    <p class="text-xs text-muted-foreground">{{ c.label }}</p>
                    <div class="flex items-center gap-2">
                        <code
                            class="flex-1 overflow-x-auto rounded-lg border bg-muted/50 px-3 py-2 text-xs whitespace-nowrap"
                            >{{ c.cmd }}</code
                        >
                        <Button
                            variant="outline"
                            size="sm"
                            class="shrink-0 gap-1.5"
                            @click="copy(c.cmd)"
                        >
                            <component
                                :is="copied === c.cmd ? Check : Copy"
                                class="size-3.5"
                            />
                            {{ copied === c.cmd ? 'Copied' : 'Copy' }}
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Per-event activity -->
        <section class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm">
            <h2 class="flex items-center gap-2 font-semibold">
                <Mail class="size-4 text-primary" /> Email activity by event
            </h2>

            <div
                v-if="events.length === 0"
                class="rounded-xl border border-dashed py-12 text-center"
            >
                <p class="text-sm font-medium">No sign-ups yet</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Register for an event from the
                    <Link
                        href="/events-visual-1"
                        class="text-primary hover:underline"
                        >Gallery</Link
                    >, then refresh.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead
                        class="border-b text-left text-xs text-muted-foreground"
                    >
                        <tr>
                            <th class="py-2 pr-3 font-medium">Event</th>
                            <th class="px-3 py-2 font-medium">When</th>
                            <th class="px-3 py-2 text-center font-medium">
                                Sign-ups
                            </th>
                            <th class="px-3 py-2 text-center font-medium">
                                Confirmed
                            </th>
                            <th class="px-3 py-2 text-center font-medium">
                                3-day
                            </th>
                            <th class="px-3 py-2 text-center font-medium">
                                24-hour
                            </th>
                            <th class="px-3 py-2 text-right font-medium">
                                Preview emails
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="ev in events"
                            :key="ev.id"
                            class="align-middle"
                        >
                            <td class="py-3 pr-3">
                                <Link
                                    :href="`/events/${ev.id}`"
                                    class="font-medium hover:text-primary"
                                    >{{ ev.name }}</Link
                                >
                                <p class="text-xs text-muted-foreground">
                                    {{ ev.location }}
                                </p>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <p>{{ ev.starts_at_label }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ relativeTime(ev.starts_at_utc) }}
                                </p>
                            </td>
                            <td class="px-3 py-3 text-center font-semibold">
                                {{ ev.attendees_count }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span
                                    class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ ev.confirmed_count }}/{{
                                        ev.attendees_count
                                    }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        ev.reminded_3d_count > 0
                                            ? 'bg-primary/15 text-primary'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ ev.reminded_3d_count }}/{{
                                        ev.attendees_count
                                    }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        ev.reminded_24h_count > 0
                                            ? 'bg-primary/15 text-primary'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ ev.reminded_24h_count }}/{{
                                        ev.attendees_count
                                    }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex gap-1.5">
                                    <a
                                        :href="`/dev/emails/${ev.id}/confirmation`"
                                        target="_blank"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-accent"
                                        >Confirm.</a
                                    >
                                    <a
                                        :href="`/dev/emails/${ev.id}/reminder/3-day`"
                                        target="_blank"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-accent"
                                        >3-day</a
                                    >
                                    <a
                                        :href="`/dev/emails/${ev.id}/reminder/24-hour`"
                                        target="_blank"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-accent"
                                        >24-hr</a
                                    >
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
