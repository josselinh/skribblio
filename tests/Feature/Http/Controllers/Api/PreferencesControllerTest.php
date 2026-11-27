<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Enums\EmailNotification;
use App\Enums\InterfaceLocale;
use App\Enums\Locale;
use App\Enums\Style;
use App\Enums\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_for_guest(): void
    {
        $this->patchJson('/api/user/preferences', ['theme' => 'dark'])->assertUnauthorized();
    }

    public function test_returns_403_for_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->patchJson('/api/user/preferences', ['theme' => 'dark'])->assertForbidden();
    }

    public function test_updates_theme_and_only_the_given_emails(): void
    {
        $user = User::factory()->create(['email_notifications' => [EmailNotification::AccessRequestDecided->value => true]]);

        $response = $this->actingAs($user)->patchJson('/api/user/preferences', [
            'theme' => 'dark',
            'email_notifications' => [EmailNotification::WordGroupFavorited->value => true],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.email_notifications', [
                'word_group_access_requested' => false,
                'group_collection_access_requested' => false,
                'access_request_decided' => true,
                'share_type_changed' => false,
                'word_group_favorited' => true,
                'group_collection_favorited' => false,
            ]);
        $user->refresh();
        $this->assertSame(Theme::Dark, $user->theme);
        $this->assertTrue($user->wantsEmail(EmailNotification::AccessRequestDecided));
        $this->assertTrue($user->wantsEmail(EmailNotification::WordGroupFavorited));
        $this->assertFalse($user->wantsEmail(EmailNotification::GroupCollectionFavorited));
    }

    public function test_updates_the_style_without_changing_the_theme(): void
    {
        $user = User::factory()->create(['theme' => Theme::Dark]);

        $this->actingAs($user)
            ->patchJson('/api/user/preferences', ['style' => 'colorful'])
            ->assertOk()
            ->assertJsonPath('data.style', 'colorful')
            ->assertJsonPath('data.theme', 'dark');

        $user->refresh();
        $this->assertSame(Style::Colorful, $user->style);
        $this->assertSame(Theme::Dark, $user->theme);
    }

    public function test_updates_the_interface_and_content_languages_independently(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/user/preferences', ['locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.content_locale', 'fr');
        $this->actingAs($user)
            ->patchJson('/api/user/preferences', ['content_locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.content_locale', 'en');

        $user->refresh();
        $this->assertSame(InterfaceLocale::En, $user->locale);
        $this->assertSame(Locale::En, $user->content_locale);
    }

    public function test_chinese_is_offered_for_the_interface_but_not_for_the_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/user/preferences', ['locale' => 'zh'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'zh');
        $this->actingAs($user)
            ->patchJson('/api/user/preferences', ['content_locale' => 'zh'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content_locale']);

        $this->assertSame(InterfaceLocale::Zh, $user->fresh()->locale);
        $this->assertSame(Locale::Fr, $user->fresh()->content_locale);
    }

    public function test_turning_an_email_back_off_stops_it(): void
    {
        $user = User::factory()->wantsEmails()->create();

        $this->actingAs($user)
            ->patchJson('/api/user/preferences', ['email_notifications' => [EmailNotification::WordGroupFavorited->value => false]])
            ->assertOk();

        $this->assertFalse($user->fresh()->wantsEmail(EmailNotification::WordGroupFavorited));
    }

    public function test_rejects_unknown_theme_style_and_email_with_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/user/preferences', ['theme' => 'pink', 'style' => 'neon', 'locale' => 'de', 'email_notifications' => ['newsletter' => true]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'theme' => 'Le champ thème sélectionné est invalide.',
                'style' => 'Le champ style sélectionné est invalide.',
                'locale' => 'Le champ langue sélectionné est invalide.',
                'email_notifications',
            ]);

        $this->assertSame(Theme::System, $user->fresh()->theme);
        $this->assertSame(Style::Sober, $user->fresh()->style);
    }
}
