<script setup lang="ts">
import { MapPin, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { cn } from '@/lib/utils';
import type { CityOption } from '@/types/events';

const props = withDefaults(
    defineProps<{
        cities: CityOption[];
        emptyLabel?: string;
        id?: string;
        inputClass?: string;
    }>(),
    {
        emptyLabel: 'Anywhere',
    },
);

const model = defineModel<string>({ required: true });

const emit = defineEmits<{ change: [] }>();

const open = ref(false);
const query = ref('');
const container = ref<HTMLElement | null>(null);
const listbox = ref<HTMLElement | null>(null);
const position = ref({ top: 0, left: 0, width: 0 });

const selected = computed(
    () => props.cities.find((c) => c.city === model.value) ?? null,
);

const inputValue = computed({
    get() {
        if (open.value) {
            return query.value;
        }

        return selected.value
            ? `${selected.value.city}, ${selected.value.country}`
            : '';
    },
    set(value: string) {
        query.value = value;
        open.value = true;
    },
});

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    if (!q) {
        return props.cities;
    }

    return props.cities.filter(
        (c) =>
            c.city.toLowerCase().includes(q) ||
            c.country.toLowerCase().includes(q) ||
            c.country_code.toLowerCase().includes(q),
    );
});

const dropdownStyle = computed(() => ({
    top: `${position.value.top}px`,
    left: `${position.value.left}px`,
    width: `${position.value.width}px`,
}));

function syncPosition() {
    if (!container.value) {
        return;
    }

    const rect = container.value.getBoundingClientRect();
    position.value = {
        top: rect.bottom + 4,
        left: rect.left,
        width: rect.width,
    };
}

function openDropdown() {
    open.value = true;
    query.value = selected.value?.city ?? '';
    syncPosition();
}

function closeDropdown() {
    open.value = false;
    query.value = '';
}

function selectCity(city: string) {
    model.value = city;
    closeDropdown();
    emit('change');
}

function clearSelection() {
    model.value = '';
    closeDropdown();
    emit('change');
}

function onClickOutside(event: MouseEvent) {
    const target = event.target as Node;

    if (
        container.value?.contains(target) ||
        listbox.value?.contains(target)
    ) {
        return;
    }

    closeDropdown();
}

function onScrollOrResize() {
    if (open.value) {
        syncPosition();
    }
}

watch(open, (isOpen) => {
    if (isOpen) {
        syncPosition();
    }
});

onMounted(() => {
    document.addEventListener('click', onClickOutside);
    window.addEventListener('scroll', onScrollOrResize, true);
    window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    window.removeEventListener('scroll', onScrollOrResize, true);
    window.removeEventListener('resize', onScrollOrResize);
});
</script>

<template>
    <div ref="container" class="relative">
        <MapPin
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <input
            :id="id"
            v-model="inputValue"
            type="text"
            role="combobox"
            :aria-expanded="open"
            autocomplete="off"
            :placeholder="emptyLabel"
            :class="
                cn(
                    'h-9 w-full rounded-md border border-input bg-background pr-9 pl-9 text-sm placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring/30 focus:outline-none',
                    inputClass,
                )
            "
            @focus="openDropdown"
        />
        <button
            v-if="model"
            type="button"
            class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-muted-foreground hover:text-foreground"
            aria-label="Clear location"
            @click.stop="clearSelection"
        >
            <X class="size-4" />
        </button>

        <Teleport to="body">
            <ul
                v-if="open"
                ref="listbox"
                role="listbox"
                class="fixed z-[200] max-h-60 overflow-auto rounded-md border bg-popover py-1 text-sm shadow-lg"
                :style="dropdownStyle"
            >
                <li>
                    <button
                        type="button"
                        role="option"
                        class="w-full px-3 py-2 text-left hover:bg-accent"
                        :class="!model ? 'bg-accent font-medium' : ''"
                        @click="clearSelection"
                    >
                        {{ emptyLabel }}
                    </button>
                </li>
                <li v-if="filtered.length === 0">
                    <p class="px-3 py-2 text-muted-foreground">No cities found</p>
                </li>
                <li v-for="c in filtered" :key="`${c.city}-${c.country_code}`">
                    <button
                        type="button"
                        role="option"
                        class="w-full px-3 py-2 text-left hover:bg-accent"
                        :class="
                            model === c.city ? 'bg-accent font-medium' : ''
                        "
                        @click="selectCity(c.city)"
                    >
                        {{ c.city }}, {{ c.country }}
                    </button>
                </li>
            </ul>
        </Teleport>
    </div>
</template>
