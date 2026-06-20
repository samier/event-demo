<script setup lang="ts">
import { CalendarClock, MapPin } from '@lucide/vue';
import { ref } from 'vue';
import RegisterInterestForm from '@/components/events/RegisterInterestForm.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { venueTime } from '@/lib/eventFormat';
import type { EventResource, RegistrationResult } from '@/types/events';

defineProps<{ event: EventResource }>();
const emit = defineEmits<{ registered: [count: number] }>();

const open = ref(false);

function onRegistered(result: RegistrationResult) {
    emit('registered', result.attendees_count);
    // Give the success state a moment to show, then close.
    window.setTimeout(() => (open.value = false), 1400);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Register for this event</DialogTitle>
                <DialogDescription class="sr-only">
                    Enter your details to join the attendee list for
                    {{ event.name }}.
                </DialogDescription>
            </DialogHeader>

            <!-- Event context so the dialog is self-explanatory -->
            <div class="flex gap-3 rounded-xl border bg-muted/40 p-3">
                <img
                    :src="event.images[0]"
                    :alt="event.name"
                    class="size-16 shrink-0 rounded-lg object-cover"
                />
                <div class="min-w-0 space-y-1">
                    <p class="truncate leading-tight font-semibold">
                        {{ event.name }}
                    </p>
                    <p
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <CalendarClock class="size-3.5 shrink-0" />
                        <span class="truncate">{{ venueTime(event) }}</span>
                    </p>
                    <p
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <MapPin class="size-3.5 shrink-0" />
                        <span class="truncate">{{ event.location.label }}</span>
                    </p>
                </div>
            </div>

            <RegisterInterestForm
                :event="event"
                id-prefix="dialog"
                @registered="onRegistered"
            />
        </DialogContent>
    </Dialog>
</template>
