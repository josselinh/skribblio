<script setup lang="ts">
/**
 * Small line icon shown before the label of a button or a menu item (`size-4` by default, in the text colour).
 * Decorative only: the label stays the accessible name, so the icon is hidden from screen readers.
 */
export type IconName =
    | 'add-to-collection'
    | 'arrow-down'
    | 'arrow-up'
    | 'check'
    | 'contribute'
    | 'copy'
    | 'delete'
    | 'duplicate'
    | 'export'
    | 'info'
    | 'invite'
    | 'leave'
    | 'manage'
    | 'plus'
    | 'preferences'
    | 'profile'
    | 'regenerate'
    | 'search'
    | 'security'
    | 'shuffle';

defineProps<{
    name: IconName;
}>();
</script>

<template>
    <svg
        aria-hidden="true"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="size-4 shrink-0"
    >
        <template v-if="name === 'plus'">
            <path d="M12 5v14M5 12h14" />
        </template>
        <template v-else-if="name === 'search'">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-4-4" />
        </template>
        <template v-else-if="name === 'copy'">
            <rect x="9" y="9" width="11" height="11" rx="2" />
            <path d="M5 15H4.5A1.5 1.5 0 0 1 3 13.5v-9A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5V5" />
        </template>
        <template v-else-if="name === 'check'">
            <path d="m5 12.5 4.5 4.5L19 7" />
        </template>
        <template v-else-if="name === 'export'">
            <path d="M12 4v11M7 10l5 5 5-5M5 20h14" />
        </template>
        <template v-else-if="name === 'duplicate'">
            <rect x="8" y="8" width="13" height="13" rx="2" />
            <path d="M4 16a1 1 0 0 1-1-1V5a2 2 0 0 1 2-2h10a1 1 0 0 1 1 1M14.5 11.5v6M11.5 14.5h6" />
        </template>
        <template v-else-if="name === 'add-to-collection'">
            <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
            <path d="M12 10v6M9 13h6" />
        </template>
        <template v-else-if="name === 'info'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5M12 8h.01" />
        </template>
        <template v-else-if="name === 'invite'">
            <circle cx="9" cy="8" r="4" />
            <path d="M2 20a7 7 0 0 1 14 0M19 8v6M16 11h6" />
        </template>
        <template v-else-if="name === 'delete'">
            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
        </template>
        <template v-else-if="name === 'manage'">
            <path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16Z" />
            <path d="m13.5 6.5 4 4" />
        </template>
        <template v-else-if="name === 'contribute'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v8M8 12h8" />
        </template>
        <template v-else-if="name === 'leave'">
            <path d="M9 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h3M16 16l4-4-4-4M20 12H10" />
        </template>
        <template v-else-if="name === 'shuffle'">
            <path d="M3 7h4l10 10h4M3 17h4l3-3M14 10l3-3h4M18 4l3 3-3 3M18 14l3 3-3 3" />
        </template>
        <template v-else-if="name === 'regenerate'">
            <path d="M20 11a8 8 0 0 0-14.9-3.5M4 4v4h4M4 13a8 8 0 0 0 14.9 3.5M20 20v-4h-4" />
        </template>
        <template v-else-if="name === 'profile'">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 20a8 8 0 0 1 16 0" />
        </template>
        <template v-else-if="name === 'security'">
            <rect x="5" y="11" width="14" height="10" rx="2" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
        </template>
        <template v-else-if="name === 'arrow-up'">
            <path d="M12 19V5M6 11l6-6 6 6" />
        </template>
        <template v-else-if="name === 'arrow-down'">
            <path d="M12 5v14M6 13l6 6 6-6" />
        </template>
        <template v-else-if="name === 'preferences'">
            <path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1" />
            <circle cx="15" cy="6" r="2" />
            <circle cx="9" cy="12" r="2" />
            <circle cx="17" cy="18" r="2" />
        </template>
    </svg>
</template>
