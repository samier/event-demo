<script setup lang="ts">
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        images: string[];
        alt: string;
        rounded?: boolean;
    }>(),
    { rounded: false },
);

const active = ref(0);

function go(index: number) {
    active.value = (index + props.images.length) % props.images.length;
}
</script>

<template>
    <div
        class="group/carousel relative h-full w-full overflow-hidden"
        :class="rounded ? 'rounded-xl' : ''"
    >
        <transition-group name="fade" tag="div" class="h-full w-full">
            <img
                v-for="(src, i) in images"
                v-show="i === active"
                :key="src"
                :src="src"
                :alt="alt"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover/carousel:scale-105"
            />
        </transition-group>

        <!-- Prev / next, revealed on hover -->
        <template v-if="images.length > 1">
            <button
                type="button"
                class="absolute top-1/2 left-2 z-10 grid size-8 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-white opacity-0 backdrop-blur transition group-hover/carousel:opacity-100 hover:bg-black/60"
                aria-label="Previous image"
                @click.stop.prevent="go(active - 1)"
            >
                ‹
            </button>
            <button
                type="button"
                class="absolute top-1/2 right-2 z-10 grid size-8 -translate-y-1/2 place-items-center rounded-full bg-black/40 text-white opacity-0 backdrop-blur transition group-hover/carousel:opacity-100 hover:bg-black/60"
                aria-label="Next image"
                @click.stop.prevent="go(active + 1)"
            >
                ›
            </button>

            <div
                class="absolute bottom-2 left-1/2 z-10 flex -translate-x-1/2 gap-1.5"
            >
                <button
                    v-for="(src, i) in images"
                    :key="src"
                    type="button"
                    class="size-1.5 rounded-full transition-all"
                    :class="
                        i === active
                            ? 'w-4 bg-white'
                            : 'bg-white/50 hover:bg-white/80'
                    "
                    :aria-label="`Go to image ${i + 1}`"
                    @click.stop.prevent="go(i)"
                />
            </div>
        </template>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
