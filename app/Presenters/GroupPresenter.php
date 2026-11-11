<?php

namespace App\Presenters;

use App\Models\Group;
use Illuminate\Support\HtmlString;

class GroupPresenter
{
    public function __construct(private Group $group)
    {
    }

    public function name(): HtmlString
    {
        $name = e($this->group->name);

        if (!$this->group->isPublicUnalterable()) {
            return new HtmlString($name);
        }

        $title = e(__('group.visibility.public_unalterable'));

        return new HtmlString(<<<HTML
            <span class="inline-flex items-center gap-1.5">
                <span>{$name}</span>
                <span title="{$title}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </span>
            </span>
            HTML);
    }
}
