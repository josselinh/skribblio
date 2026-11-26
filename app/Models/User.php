<?php

namespace App\Models;

use App\Enums\EmailNotification;
use App\Enums\InterfaceLocale;
use App\Enums\Locale;
use App\Enums\Theme;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'theme', 'locale', 'content_locale', 'email_notifications'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Default values, matching the database defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'locale' => 'fr',
        'content_locale' => 'fr',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'theme' => Theme::class,
            'locale' => InterfaceLocale::class,
            'content_locale' => Locale::class,
            'email_notifications' => 'array',
        ];
    }

    /**
     * Language of the emails sent to the user: the one they chose for the interface.
     */
    public function preferredLocale(): string
    {
        return ($this->locale ?? InterfaceLocale::Fr)->value;
    }

    /**
     * Whether the user wants to receive this kind of email (no unless they turned it on).
     */
    public function wantsEmail(EmailNotification $notification): bool
    {
        return (bool) ($this->email_notifications[$notification->value] ?? false);
    }

    /**
     * Every kind of email with whether the user receives it.
     *
     * @return array<string, bool>
     */
    public function emailNotificationPreferences(): array
    {
        return collect(EmailNotification::cases())
            ->mapWithKeys(fn (EmailNotification $notification): array => [$notification->value => $this->wantsEmail($notification)])
            ->all();
    }

    /**
     * Word groups created by the user.
     *
     * @return HasMany<WordGroup, $this>
     */
    public function wordGroups(): HasMany
    {
        return $this->hasMany(WordGroup::class, 'created_by');
    }

    /**
     * Words created by the user, in any group.
     *
     * @return HasMany<Word, $this>
     */
    public function words(): HasMany
    {
        return $this->hasMany(Word::class, 'created_by');
    }

    /**
     * Group collections created by the user.
     *
     * @return HasMany<GroupCollection, $this>
     */
    public function groupCollections(): HasMany
    {
        return $this->hasMany(GroupCollection::class, 'created_by');
    }

    /**
     * @return BelongsToMany<WordGroup, $this>
     */
    public function favoriteWordGroups(): BelongsToMany
    {
        return $this->belongsToMany(WordGroup::class, 'favorite_word_groups')->withTimestamps();
    }

    /**
     * @return BelongsToMany<GroupCollection, $this>
     */
    public function favoriteGroupCollections(): BelongsToMany
    {
        return $this->belongsToMany(GroupCollection::class, 'favorite_group_collections')->withTimestamps();
    }

    /**
     * Private groups of other users this user was granted access to.
     *
     * @return BelongsToMany<WordGroup, $this>
     */
    public function sharedWordGroups(): BelongsToMany
    {
        return $this->belongsToMany(WordGroup::class, 'word_group_members')->withTimestamps();
    }

    /**
     * Private collections of other users this user was granted access to.
     *
     * @return BelongsToMany<GroupCollection, $this>
     */
    public function sharedGroupCollections(): BelongsToMany
    {
        return $this->belongsToMany(GroupCollection::class, 'group_collection_members')->withTimestamps();
    }
}
