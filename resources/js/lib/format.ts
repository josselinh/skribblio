import { intlLocale } from '@/lib/i18n';

const dateFormatters = new Map<string, Intl.DateTimeFormat>();

/** Formats an ISO 8601 date for display in the interface's language, e.g. "18 nov. 2026, 17:08". */
export function formatDateTime(isoDate: string | null): string {
    if (!isoDate) {
        return '';
    }
    const locale = intlLocale();
    let formatter = dateFormatters.get(locale);
    if (!formatter) {
        formatter = new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' });
        dateFormatters.set(locale, formatter);
    }

    return formatter.format(new Date(isoDate));
}
