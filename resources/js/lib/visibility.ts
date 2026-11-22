import { i18n } from '@/lib/i18n';
import type { Visibility } from '@/types/models';

/** "Public" or "Privé". */
export function visibilityLabel(visibility: Visibility): string {
    return i18n.global.t(`visibility.${visibility}.label`);
}

/** What the visibility means, e.g. "Visible par vous uniquement.". */
export function visibilityDescription(visibility: Visibility): string {
    return i18n.global.t(`visibility.${visibility}.description`);
}
