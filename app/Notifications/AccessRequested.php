<?php

namespace App\Notifications;

use App\Models\GroupCollectionAccessRequest;
use App\Models\WordGroupAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the creator of a private group or collection that someone asked to access it.
 */
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

        return (new MailMessage)
            ->subject("{$requesterName} demande l'accès {$this->accessRequest->accessTarget()}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$requesterName} demande l'accès {$this->accessRequest->accessTarget()}.")
            ->action('Voir la demande', url($this->accessRequest->itemPath()))
            ->line('Vous pouvez accepter ou refuser la demande depuis cette page ou depuis votre tableau de bord.');
    }
}
