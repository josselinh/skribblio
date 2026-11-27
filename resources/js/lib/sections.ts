/**
 * Panel holding a list of a page (cards of the dashboard and the Explorer, groups of a collection, words of a
 * group), like the sections of the edit pages, so that each list stands apart from the next.
 */
export const listPanelClass = 'rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-xs sm:p-6';

/**
 * Header of a list panel, underlined across it: the title with its total on the left, optional actions (a button,
 * the sorting) at the end. The same height with or without actions, so that the line falls at the same place.
 */
export const listPanelHeaderClass =
    'flex min-h-12 flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-zinc-200 pb-3 dark:border-zinc-800';
