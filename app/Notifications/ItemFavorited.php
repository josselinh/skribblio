<?php

namespace App\Notifications;

use App\Enums\EmailNotification;
use App\Models\GroupCollection;
use App\Models\User;
use App\Models\WordGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Tells the creator of a group or collection that someone added it to their favorites.
 *
 * Dropped when the item or one of the users was deleted before it was sent.
 */
#[DeleteWhenMissingModels]
class ItemFavorited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WordGroup|GroupCollection $item, public User $favoritedBy) {}

    /**
     * Get the notification's delivery channels: none when the creator turned these emails off.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $kind = $this->item instanceof WordGroup
            ? EmailNotification::WordGroupFavorited
            : EmailNotification::GroupCollectionFavorited;

        return $notifiable->wantsEmail($kind) ? ['mail'] : [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        [$kind, $path] = $this->item instanceof WordGroup
            ? ['word_group', "/groups/{$this->item->getKey()}"]
            : ['group_collection', "/collections/{$this->item->getKey()}"];
        $message = __("favorites.mail.{$kind}", ['user' => $this->favoritedBy->name, 'name' => $this->item->name]);

        return (new MailMessage)
            ->subject($message)
            ->greeting(__('favorites.mail.greeting', ['name' => $notifiable->name]))
            ->line("{$message}.")
            ->action(__('favorites.mail.open'), url($path))
            ->line(__('favorites.mail.outro'));
    }
}
