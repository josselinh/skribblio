<?php

namespace App\Notifications;

use App\Models\GroupCollectionAccessRequest;
use App\Models\WordGroupAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Tells the creator of a group or collection that someone asked to access it (when private) or to
 * contribute to it (when public and open to its members only).
 *
 * Dropped when the request or the user was deleted (e.g. an account deleted) before it was sent.
 */
#[DeleteWhenMissingModels]
class AccessRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WordGroupAccessRequest|GroupCollectionAccessRequest $accessRequest) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $requesterName = $this->accessRequest->requester->name;
        $request = $this->accessRequest->isForContribution()
            ? "{$requesterName} demande à contribuer {$this->accessRequest->accessTarget()}"
            : "{$requesterName} demande l'accès {$this->accessRequest->accessTarget()}";

        return (new MailMessage)
            ->subject($request)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$request}.")
            ->action('Voir la demande', url($this->accessRequest->itemPath()))
            ->line('Vous pouvez accepter ou refuser la demande depuis cette page ou depuis votre tableau de bord.');
    }
}
