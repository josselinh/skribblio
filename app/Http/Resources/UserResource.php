<?php

namespace App\Http\Resources;

use App\Enums\Locale;
use App\Enums\Theme;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The current user, with their preferences (only ever sent to themselves).
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, name: string, email: string, email_verified_at: ?string, theme: string, locale: string, email_notifications: array<string, bool>, created_at: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'theme' => ($this->theme ?? Theme::System)->value,
            'locale' => ($this->locale ?? Locale::Fr)->value,
            'email_notifications' => $this->emailNotificationPreferences(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
