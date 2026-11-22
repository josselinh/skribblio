import { intlLocale } from '@/lib/i18n';

const dateFormatter = new Intl.DateTimeFormat(intlLocale, { dateStyle: 'medium', timeStyle: 'short' });

/** Formats an ISO 8601 date for display, e.g. "18 nov. 2026, 17:08". */
export function formatDateTime(isoDate: string | null): string {
    return isoDate ? dateFormatter.format(new Date(isoDate)) : '';
}
