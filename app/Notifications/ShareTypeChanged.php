<?php

namespace App\Notifications;

use App\Enums\EmailNotification;
use App\Enums\ShareType;
use App\Models\GroupCollection;
use App\Models\WordGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Tells a member that the creator changed their type of sharing (partial or full) of a group or collection.
 *
 * Dropped when the item or the member was deleted before it was sent.
 */
#[DeleteWhenMissingModels]
class ShareTypeChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WordGroup|GroupCollection $item, public ShareType $shareType) {}

    /**
     * Get the notification's delivery channels: none when the member turned these emails off.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmail(EmailNotification::ShareTypeChanged) ? ['mail'] : [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        [$kind, $path] = $this->item instanceof WordGroup
            ? ['word_group', "/groups/{$this->item->getKey()}"]
            : ['group_collection', "/collections/{$this->item->getKey()}"];
        $replacements = ['creator' => $this->item->creator->name, 'name' => $this->item->name];

        return (new MailMessage)
            ->subject(__("access_requests.mail.share_type_changed.subject.{$kind}", $replacements))
            ->greeting(__('access_requests.mail.greeting', ['name' => $notifiable->name]))
            ->line(__("access_requests.mail.share_type_changed.line.{$kind}", $replacements))
            ->line(__("access_requests.mail.share_type.{$this->shareType->value}.{$kind}"))
            ->action(__('access_requests.mail.open'), url($path));
    }
}
