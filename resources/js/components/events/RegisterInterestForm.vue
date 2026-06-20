<script setup lang="ts">
import { CheckCircle2, LoaderCircle, Mail, User } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useRegisterInterest } from '@/composables/useRegisterInterest';
import type { EventResource, RegistrationResult } from '@/types/events';

const props = defineProps<{
    event: EventResource;
    idPrefix?: string;
}>();

const emit = defineEmits<{ registered: [result: RegistrationResult] }>();

const { form, errors, processing, succeeded, submit, reset } =
    useRegisterInterest(props.event, (result) => emit('registered', result));

const field = (name: string) => `${props.idPrefix ?? 'reg'}-${name}`;
</script>

<template>
    <!-- Success confirmation -->
    <div
        v-if="succeeded"
        class="flex animate-in flex-col items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-5 text-center duration-300 fade-in-0 zoom-in-95"
    >
        <CheckCircle2 class="size-9 text-emerald-500" />
        <div class="space-y-1">
            <p class="font-semibold">You're on the list!</p>
            <p class="text-sm text-muted-foreground">
                A confirmation email is on its way. We'll remind you 3 days and
                24 hours before.
            </p>
        </div>
        <Button variant="outline" size="sm" @click="reset"
            >Register someone else</Button
        >
    </div>

    <!-- Form -->
    <form v-else class="grid gap-4" @submit.prevent="submit">
        <div class="grid gap-1.5">
            <Label :for="field('name')">Full name</Label>
            <div class="relative">
                <User
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    :id="field('name')"
                    v-model="form.name"
                    class="pl-9"
                    placeholder="Ada Lovelace"
                    autocomplete="name"
                    :aria-invalid="!!errors.name"
                />
            </div>
            <p v-if="errors.name" class="text-sm text-destructive">
                {{ errors.name }}
            </p>
        </div>

        <div class="grid gap-1.5">
            <Label :for="field('email')">Email address</Label>
            <div class="relative">
                <Mail
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    :id="field('email')"
                    v-model="form.email"
                    type="email"
                    class="pl-9"
                    placeholder="ada@example.com"
                    autocomplete="email"
                    :aria-invalid="!!errors.email"
                />
            </div>
            <p v-if="errors.email" class="text-sm text-destructive">
                {{ errors.email }}
            </p>
        </div>

        <Button type="submit" class="w-full" :disabled="processing">
            <LoaderCircle v-if="processing" class="size-4 animate-spin" />
            {{ processing ? 'Registering…' : 'Confirm registration' }}
        </Button>

        <p class="text-center text-xs text-muted-foreground">
            We'll only use your email to send the confirmation and event
            reminders.
        </p>
    </form>
</template>
