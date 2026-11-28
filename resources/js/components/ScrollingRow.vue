<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, onUpdated, ref, useTemplateRef } from 'vue';

/**
 * Row of pills (filters, sorts) that stays on one line and scrolls horizontally, swiped with a finger, when they do not
 * fit; whatever comes before it (the "Tri :" prefix) stays in place. The scrollbar is hidden: a fade on each side tells
 * that pills are out of view there. On mount, the selected pill (`aria-pressed="true"` or a checked radio) is brought into
 * view. Pass the gap and, for touch screens, the vertical padding that keeps the enlarged hit areas of the pills unclipped.
 */
const row = useTemplateRef<HTMLDivElement>('row');
const isStartHidden = ref(false);
const isEndHidden = ref(false);

/** Width of the fade on each side. */
const FADE = '1.5rem';

function update(): void {
    const element = row.value;
    if (!element) {
        return;
    }
    isStartHidden.value = element.scrollLeft > 0;
    isEndHidden.value = element.scrollLeft + element.clientWidth < element.scrollWidth - 1;
}

function showSelected(): void {
    const element = row.value;
    const selected = element?.querySelector<HTMLElement>('[aria-pressed="true"], label:has(input:checked)');
    if (!element || !selected || selected.offsetLeft + selected.offsetWidth <= element.clientWidth) {
        return;
    }
    element.scrollLeft = selected.offsetLeft + selected.offsetWidth - element.clientWidth;
}

let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
    showSelected();
    update();
    resizeObserver = new ResizeObserver(update);
    if (row.value) {
        resizeObserver.observe(row.value);
    }
});
/** The labels may change (another language, a pill added), so may the room they take. */
onUpdated(update);
onBeforeUnmount(() => resizeObserver?.disconnect());

const maskStyle = computed(() => {
    if (!isStartHidden.value && !isEndHidden.value) {
        return undefined;
    }
    const start = isStartHidden.value ? `transparent, black ${FADE}` : 'black';
    const end = isEndHidden.value ? `black calc(100% - ${FADE}), transparent` : 'black';
    const gradient = `linear-gradient(to right, ${start}, ${end})`;

    return { maskImage: gradient, WebkitMaskImage: gradient };
});
</script>

<template>
    <!-- `relative`: keeps what is positioned inside (sr-only inputs, hit areas) within the clipping of the scrolling row. -->
    <div
        ref="row"
        class="relative flex min-w-0 items-center overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        :style="maskStyle"
        @scroll.passive="update"
    >
        <slot />
    </div>
</template>
