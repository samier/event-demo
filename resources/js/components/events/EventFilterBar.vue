<script setup lang="ts">
import { Search, SlidersHorizontal, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import CitySearchSelect from '@/components/events/CitySearchSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { CityOption, EventFilters, WhenFilter } from '@/types/events';

// `filters` is a shared reactive object owned by the page (via useEventFeed);
// defineModel lets us read and write it two-way without tripping no-mutating-props.
const filters = defineModel<EventFilters>('filters', { required: true });

defineProps<{
    cities: CityOption[];
    categories: string[];
    total: number | null;
}>();

const emit = defineEmits<{ change: []; reset: [] }>();

const whenOptions: { value: WhenFilter; label: string }[] = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'past', label: 'Past' },
    { value: 'all', label: 'All dates' },
];

// Debounce free-text search so we don't refetch on every keystroke.
let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => filters.value.q,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => emit('change'), 350);
    },
);

const showAdvanced = ref(false);

function setWhen(value: WhenFilter) {
    filters.value.when = value;
    emit('change');
}

function selectCategory(category: string) {
    filters.value.type = filters.value.type === category ? '' : category;
    emit('change');
}
</script>

<template>
    <div class="relative z-20 rounded-2xl border bg-card/60 p-4 shadow-sm backdrop-blur">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.q"
                    placeholder="Search events…"
                    class="pl-9"
                />
            </div>

            <div class="inline-flex rounded-lg border bg-background p-0.5">
                <button
                    v-for="opt in whenOptions"
                    :key="opt.value"
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
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

            <Button
                variant="outline"
                size="sm"
                class="gap-2"
                @click="showAdvanced = !showAdvanced"
            >
                <SlidersHorizontal class="size-4" />
                Filters
            </Button>
        </div>

        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition-all duration-150 ease-in"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-show="showAdvanced"
                class="mt-4 grid gap-4 border-t pt-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-muted-foreground"
                        >Location</label
                    >
                    <CitySearchSelect
                        v-model="filters.city"
                        :cities="cities"
                        empty-label="Anywhere"
                        @change="emit('change')"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-muted-foreground"
                        >From</label
                    >
                    <input
                        v-model="filters.from"
                        type="date"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="emit('change')"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-muted-foreground"
                        >To</label
                    >
                    <input
                        v-model="filters.to"
                        type="date"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="emit('change')"
                    />
                </div>

                <div class="flex items-end">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="gap-2 text-muted-foreground"
                        @click="emit('reset')"
                    >
                        <X class="size-4" />
                        Clear all
                    </Button>
                </div>

                <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4">
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-medium capitalize transition-colors"
                        :class="
                            filters.type === cat
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:border-primary/50 hover:bg-accent'
                        "
                        @click="selectCategory(cat)"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>
        </Transition>

        <p v-if="total !== null" class="mt-3 text-xs text-muted-foreground">
            {{ total.toLocaleString() }} event{{ total === 1 ? '' : 's' }} match
            your filters
        </p>
    </div>
</template>
