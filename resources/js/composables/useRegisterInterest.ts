import { reactive, ref } from 'vue';
import { toast } from 'vue-sonner';
import { postJson } from '@/lib/http';
import type { EventResource, RegistrationResult } from '@/types/events';

/**
 * Shared registration logic for an event, used by both the inline form on the
 * detail page and the dialog on the listing cards. Handles validation errors
 * (422), success/already-registered messaging, and surfacing a toast.
 */
export function useRegisterInterest(
    event: EventResource,
    onSuccess?: (result: RegistrationResult) => void,
) {
    const form = reactive({ name: '', email: '' });
    const errors = reactive<{ name?: string; email?: string }>({});
    const processing = ref(false);
    const succeeded = ref(false);

    function reset() {
        form.name = '';
        form.email = '';
        errors.name = undefined;
        errors.email = undefined;
        succeeded.value = false;
    }

    async function submit(): Promise<RegistrationResult | null> {
        processing.value = true;
        errors.name = undefined;
        errors.email = undefined;

        const { ok, status, data } = await postJson<RegistrationResult>(
            `/events/${event.id}/attendees`,
            { name: form.name, email: form.email },
        );

        processing.value = false;

        if (status === 422 && data.errors) {
            errors.name = data.errors.name?.[0];
            errors.email = data.errors.email?.[0];

            return null;
        }

        if (!ok) {
            toast.error('Something went wrong. Please try again.');

            return null;
        }

        succeeded.value = true;
        toast.success(data.message);
        onSuccess?.(data);

        return data;
    }

    return { form, errors, processing, succeeded, submit, reset };
}
