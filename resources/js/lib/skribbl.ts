/** Limits of the "Custom words" field of a private Skribbl.io room. */
export const SKRIBBL_MIN_WORDS = 10;
export const SKRIBBL_MAX_WORD_LENGTH = 32;
export const SKRIBBL_MAX_CHARACTERS = 20000;

/** Separator between words in the exported list. */
export const SKRIBBL_SEPARATOR = ', ';

/** Formats words as Skribbl.io expects them: separated by commas. */
export function formatSkribblList(words: readonly string[]): string {
    return words.join(SKRIBBL_SEPARATOR);
}

/** Splits a typed or pasted list into words: separated by commas or line breaks, trimmed, empty ones dropped. */
export function parseWordList(text: string): string[] {
    return text
        .split(/[,\n]/)
        .map((word) => word.trim())
        .filter((word) => word !== '');
}

/** Keeps the first occurrence of each word, ignoring case. */
export function deduplicateWords(words: readonly string[]): string[] {
    const seen = new Set<string>();

    return words.filter((word) => {
        const key = word.toLocaleLowerCase('fr');
        if (seen.has(key)) {
            return false;
        }
        seen.add(key);

        return true;
    });
}

/** Returns a shuffled copy (Fisher–Yates). */
export function shuffleWords(words: readonly string[]): string[] {
    const shuffled = [...words];
    for (let index = shuffled.length - 1; index > 0; index--) {
        const otherIndex = Math.floor(Math.random() * (index + 1));
        [shuffled[index], shuffled[otherIndex]] = [shuffled[otherIndex]!, shuffled[index]!];
    }

    return shuffled;
}

/** Random selection of words whose formatted list fits in Skribbl.io's character limit. */
export function drawWordsWithinLimit(words: readonly string[]): string[] {
    const drawn: string[] = [];
    let length = 0;
    for (const word of shuffleWords(words)) {
        const addedLength = word.length + (drawn.length > 0 ? SKRIBBL_SEPARATOR.length : 0);
        if (length + addedLength <= SKRIBBL_MAX_CHARACTERS) {
            drawn.push(word);
            length += addedLength;
        }
    }

    return drawn;
}
