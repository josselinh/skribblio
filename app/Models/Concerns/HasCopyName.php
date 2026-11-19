<?php

namespace App\Models\Concerns;

/**
 * Name given to a copy of a group or a collection: the original name followed by " (copie)",
 * shortened so that the whole name still fits in the 255 characters allowed.
 */
trait HasCopyName
{
    /**
     * Suffix added to the name of a copy.
     */
    public const string COPY_NAME_SUFFIX = ' (copie)';

    /**
     * Longest name, as validated on creation.
     */
    public const int COPY_NAME_MAX_LENGTH = 255;

    public function copyName(): string
    {
        return mb_substr($this->name, 0, self::COPY_NAME_MAX_LENGTH - mb_strlen(self::COPY_NAME_SUFFIX)).self::COPY_NAME_SUFFIX;
    }
}
