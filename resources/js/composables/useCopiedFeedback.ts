import { onBeforeUnmount, ref } from 'vue';

/** How long a copy button shows its check mark after a successful copy. */
const COPIED_DURATION_MS = 2000;

/**
 * State of a copy button: `isCopied` turns true for a moment after `showCopied()`, so that the button swaps its copy
 * icon for a check mark (the toast still confirms the copy).
 */
export function useCopiedFeedback() {
    const isCopied = ref(false);
    let timeout: ReturnType<typeof setTimeout> | undefined;

    function showCopied(): void {
        isCopied.value = true;
        clearTimeout(timeout);
        timeout = setTimeout(() => (isCopied.value = false), COPIED_DURATION_MS);
    }

    onBeforeUnmount(() => clearTimeout(timeout));

    return { isCopied, showCopied };
}
