/** Classes of an entry of `ActionsMenu` (the action buttons of a group or collection page). */
export const actionButtonClass =
    'block w-full px-4 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:bg-zinc-50 dark:focus:bg-zinc-800 focus:outline-none';

/** Destructive entry (deletion, leaving): in red, set apart from the entries above it. */
export const dangerActionButtonClass = `${actionButtonClass} border-t border-zinc-100 dark:border-zinc-800 first:border-t-0 text-red-600 dark:text-red-400`;

/**
 * How an action button (`DuplicateButton`, `ShareButton`, `DeleteButton`…) is drawn: an entry of `ActionsMenu`
 * (the collection page), or a standalone button (beside the export of a group page, on its edit page).
 */
export type ActionAppearance = 'menu-item' | 'button';

/** Classes of a standalone secondary button. */
export const secondaryButtonClass =
    'inline-flex items-center justify-center rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-60 sm:py-2';

/** Classes of a standalone destructive button (deletion, leaving). */
export const dangerButtonClass =
    'inline-flex items-center justify-center rounded-lg border border-red-300 dark:border-red-900 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 disabled:opacity-60 sm:py-2';

/** Classes of an action button in the given appearance. */
export function actionClass(appearance: ActionAppearance, isDangerous = false): string {
    if (appearance === 'button') {
        return isDangerous ? dangerButtonClass : secondaryButtonClass;
    }

    return isDangerous ? dangerActionButtonClass : actionButtonClass;
}
