/** Classes of an entry of `ActionsMenu` (the action buttons of a group or collection page). */
export const actionButtonClass =
    'block w-full px-4 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:bg-zinc-50 dark:focus:bg-zinc-800 focus:outline-none';

/** Destructive entry (deletion, leaving): in red, set apart from the entries above it. */
export const dangerActionButtonClass = `${actionButtonClass} border-t border-zinc-100 dark:border-zinc-800 first:border-t-0 text-red-600 dark:text-red-400`;
