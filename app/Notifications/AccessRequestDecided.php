<?php

namespace App\Notifications;

use App\Models\GroupCollectionAccessRequest;
use App\Models\WordGroupAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the requester that the creator accepted or refused their access request. When the request
 * was sent from a collection, the group's name is not disclosed if it was refused.
 */
class AccessRequestDecided extends Notification implements ShouldQueue
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
        $creatorName = $this->accessRequest->item->creator->name;
        $mail = (new MailMessage)->greeting("Bonjour {$notifiable->name},");

        if ($this->accessRequest->isAccepted()) {
            return $mail
                ->subject("Accès accordé {$this->accessRequest->accessTarget()}")
                ->line("{$creatorName} vous a donné accès {$this->accessRequest->accessTarget()}.")
                ->action('Ouvrir', url($this->accessRequest->itemPath()));
        }

        $accessTarget = $this->isSentFromCollection() ? 'à un groupe privé' : $this->accessRequest->accessTarget();

        return $mail
            ->subject("Demande d'accès refusée")
            ->line("{$creatorName} a refusé votre demande d'accès {$accessTarget}.");
    }

    private function isSentFromCollection(): bool
    {
        return $this->accessRequest instanceof WordGroupAccessRequest
            && $this->accessRequest->via_group_collection_id !== null;
    }
}
