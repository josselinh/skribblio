import { createI18n } from 'vue-i18n';
import fr from '@/lang/fr';
import type { FavoriteKind } from '@/types/models';

export type MessageSchema = typeof fr;

declare module 'vue-i18n' {
    export interface DefineLocaleMessage extends MessageSchema {}
}

/**
 * French plural rule: 0 and 1 take the singular. With three choices ("aucun mot | {n} mot | {n} mots"),
 * the first one is used for 0.
 */
function frenchPluralRule(choice: number, choicesLength: number): number {
    if (choicesLength === 3) {
        return Math.min(Math.abs(choice), 2);
    }

    return Math.abs(choice) > 1 ? 1 : 0;
}

/**
 * Translations of the SPA (`resources/js/lang`), in the Composition API mode. Components use `$t` in
 * templates or `useI18n()` in scripts; modules outside components use `i18n.global.t`.
 */
export const i18n = createI18n<[MessageSchema], 'fr', false>({
    legacy: false,
    locale: 'fr',
    fallbackLocale: 'fr',
    messages: { fr },
    pluralRules: { fr: frenchPluralRule },
});

/** BCP 47 tag used by the `Intl` formatters. */
export const intlLocale = 'fr-FR';

/** Key of the group or collection variant of a message, e.g. `deletion.title.${itemKey(kind)}`. */
export function itemKey(kind: FavoriteKind): 'wordGroup' | 'groupCollection' {
    return kind === 'word-groups' ? 'wordGroup' : 'groupCollection';
}
