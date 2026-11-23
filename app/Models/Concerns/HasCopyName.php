<?php

namespace App\Models\Concerns;

/**
 * Name given to a copy of a group or a collection: the original name followed by " (copie)"
 * (`duplication.copy_name` translation, in the item's language, since the name is content), shortened so
 * that the whole name still fits in the 255 characters allowed.
 */
trait HasCopyName
{
    /**
     * Longest name, as validated on creation.
     */
    public const int COPY_NAME_MAX_LENGTH = 255;

    public function copyName(): string
    {
        $locale = $this->language->value;
        $addedLength = mb_strlen(__('duplication.copy_name', ['name' => ''], $locale));

        return __('duplication.copy_name', ['name' => mb_substr($this->name, 0, self::COPY_NAME_MAX_LENGTH - $addedLength)], $locale);
    }
}
