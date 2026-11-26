<?php

namespace App\Notifications;

use App\Enums\EmailNotification;
use App\Enums\ShareType;
use App\Models\GroupCollectionAccessRequest;
use App\Models\WordGroup;
use App\Models\WordGroupAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Tells the requester that the creator accepted or refused their access (or contribution) request, and what a
 * full sharing lets them do. When the request
 * was sent from a collection, the group's name is not disclosed if it was refused.
 *
 * Dropped when the request or the user was deleted (e.g. an account deleted) before it was sent.
 */
#[DeleteWhenMissingModels]
class AccessRequestDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest) {}

    /**
     * Get the notification's delivery channels: none when the requester turned these emails off.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmail(EmailNotification::AccessRequestDecided) ? ['mail'] : [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $outcome = match (true) {
            $this->accessRequest->isForContribution() && $this->accessRequest->isAccepted() => 'contribution_accepted',
            $this->accessRequest->isForContribution() => 'contribution_refused',
            $this->accessRequest->isAccepted() => 'access_accepted',
            default => 'access_refused',
        };
        $replacements = [
            'creator' => $this->accessRequest->item->creator->name,
            'target' => $outcome === 'access_refused' && $this->isSentFromCollection()
                ? __('access_requests.target.hidden_word_group')
                : $this->accessRequest->accessTarget(),
        ];

        $mail = (new MailMessage)
            ->subject(__("access_requests.mail.{$outcome}.subject", $replacements))
            ->greeting(__('access_requests.mail.greeting', ['name' => $notifiable->name]))
            ->line(__("access_requests.mail.{$outcome}.line", $replacements));

        if (! $this->accessRequest->isAccepted()) {
            return $mail;
        }

        $item = $this->accessRequest->item;
        if ($item->shareTypeOf($notifiable) === ShareType::Full) {
            $kind = $item instanceof WordGroup ? 'word_group' : 'group_collection';
            $mail->line(__("access_requests.mail.share_type.full.{$kind}"));
        }

        return $mail->action(__('access_requests.mail.open'), url($this->accessRequest->itemPath()));
    }

    private function isSentFromCollection(): bool
    {
        return $this->accessRequest instanceof WordGroupAccessRequest
            && $this->accessRequest->via_group_collection_id !== null;
    }
}
