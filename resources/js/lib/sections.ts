/**
 * Panel holding a list of a page (cards of the dashboard and the Explorer, groups of a collection, words of a
 * group) or a section of an edit page, so that each stands apart from the next. On phones it spans the whole
 * width of the screen, without side borders, so that its content keeps the width of the page.
 */
export const listPanelClass =
    '-mx-4 border-y border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-4 py-5 shadow-xs sm:mx-0 sm:rounded-xl sm:border sm:p-6';

/**
 * Header of a list panel, underlined across it: the title with its total on the left, optional actions (a button,
 * the sorting) at the end. The same height with or without actions, so that the line falls at the same place.
 */
export const listPanelHeaderClass =
    'flex min-h-12 flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-zinc-200 pb-3 dark:border-zinc-800';
