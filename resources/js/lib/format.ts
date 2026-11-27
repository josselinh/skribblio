import { i18n, intlLocale } from '@/lib/i18n';

const dateTimeFormatters = new Map<string, Intl.DateTimeFormat>();
const dateFormatters = new Map<string, Intl.DateTimeFormat>();
const relativeTimeFormatters = new Map<string, Intl.RelativeTimeFormat>();

const MINUTE_MS = 60_000;
const HOUR_MS = 60 * MINUTE_MS;
const DAY_MS = 24 * HOUR_MS;

/** Beyond this number of days, a relative date ("il y a 5 semaines") says less than the date itself. */
const RELATIVE_MAX_DAYS = 30;

function cachedFormatter<T>(cache: Map<string, T>, create: (locale: string) => T): T {
    const locale = intlLocale();
    let formatter = cache.get(locale);
    if (!formatter) {
        formatter = create(locale);
        cache.set(locale, formatter);
    }

    return formatter;
}

/** Formats an ISO 8601 date for display in the interface's language, e.g. "18 nov. 2026, 17:08". */
export function formatDateTime(isoDate: string | null): string {
    if (!isoDate) {
        return '';
    }
    const formatter = cachedFormatter(
        dateTimeFormatters,
        (locale) => new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }),
    );

    return formatter.format(new Date(isoDate));
}

/** Number of calendar days between two dates, in the device's time zone (1 for yesterday, even 9 hours ago). */
function calendarDaysBetween(date: Date, now: Date): number {
    const startOfDay = (value: Date) => new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime();

    return Math.round((startOfDay(now) - startOfDay(date)) / DAY_MS);
}

/**
 * Formats an ISO 8601 date relatively to `now`, to follow a word such as "Mise à jour": "à l'instant",
 * "il y a 15 minutes", "hier", "il y a 3 jours", "il y a 2 semaines", then, after a month, "le 18 nov. 2026".
 * A date in the future (a clock ahead of the device's) counts as just now.
 */
export function formatRelativeTime(isoDate: string, now: Date = new Date()): string {
    const date = new Date(isoDate);
    const elapsedMs = now.getTime() - date.getTime();
    if (elapsedMs < MINUTE_MS) {
        return i18n.global.t('common.time.justNow');
    }
    const relative = cachedFormatter(relativeTimeFormatters, (locale) => new Intl.RelativeTimeFormat(locale, { numeric: 'auto' }));
    if (elapsedMs < HOUR_MS) {
        return relative.format(-Math.floor(elapsedMs / MINUTE_MS), 'minute');
    }
    if (elapsedMs < DAY_MS) {
        return relative.format(-Math.floor(elapsedMs / HOUR_MS), 'hour');
    }
    const days = Math.max(calendarDaysBetween(date, now), 1);
    if (days < 7) {
        return relative.format(-days, 'day');
    }
    if (days <= RELATIVE_MAX_DAYS) {
        // "il y a 1 semaine" rather than "la semaine dernière", which would mean the previous calendar week.
        return new Intl.RelativeTimeFormat(intlLocale(), { numeric: 'always' }).format(-Math.floor(days / 7), 'week');
    }
    const formatter = cachedFormatter(dateFormatters, (locale) => new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }));

    return i18n.global.t('common.time.onDate', { date: formatter.format(date) });
}
