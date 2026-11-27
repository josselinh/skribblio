/**
 * Classes of the `<li>` holding an `ItemCard` in a card grid: it spans the four rows of a card (title, badges,
 * creator and date, footer) and passes them on as a subgrid, so that these parts line up across the cards of a
 * grid row whatever the height of each (badges or footer on two lines). The cards' own gaps replace the grid's.
 */
export const cardGridItemClass = 'row-span-4 grid grid-rows-subgrid gap-y-0';
