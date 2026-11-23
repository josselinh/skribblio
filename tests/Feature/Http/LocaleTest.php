<?php

namespace Tests\Feature\Http;

use App\Enums\Locale;
use App\Models\User;
use App\Models\WordGroup;
use App\Notifications\ItemFavorited;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_gets_messages_in_the_language_sent_by_the_spa(): void
    {
        $this->postJson('/register', [], ['X-Locale' => 'en'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'The username field is required.']);
    }

    public function test_unknown_language_falls_back_to_french(): void
    {
        $this->postJson('/register', [], ['X-Locale' => 'de'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Le champ pseudo est obligatoire.']);
    }

    public function test_logged_in_user_gets_messages_in_their_language_whatever_the_header(): void
    {
        $user = User::factory()->create(['locale' => Locale::En]);
        $wordGroup = WordGroup::factory()->createdBy($user)->create();

        $this->actingAs($user)
            ->postJson("/api/word-groups/{$wordGroup->id}/words", [], ['X-Locale' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['names' => 'Enter at least one word.']);
    }

    public function test_registration_keeps_the_language_used_and_sends_the_verification_email_in_it(): void
    {
        Notification::fake();

        $this->postJson('/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ], ['X-Locale' => 'en'])->assertCreated();

        $user = User::firstWhere('email', 'alice@example.com');
        $this->assertSame(Locale::En, $user->locale);
        Notification::assertSentTo($user, VerifyEmail::class, fn ($notification, array $channels, object $notifiable, ?string $locale): bool => $locale === 'en');
    }

    public function test_emails_are_sent_in_the_recipients_language_not_the_senders(): void
    {
        Notification::fake();
        $creator = User::factory()->wantsEmails()->create(['locale' => Locale::En]);
        $wordGroup = WordGroup::factory()->public()->createdBy($creator)->create();

        $this->actingAs(User::factory()->create(['locale' => Locale::Fr]))
            ->putJson("/api/word-groups/{$wordGroup->id}/favorite")
            ->assertNoContent();

        Notification::assertSentTo($creator, ItemFavorited::class, fn ($notification, array $channels, object $notifiable, ?string $locale): bool => $locale === 'en');
    }

    public function test_every_translation_of_the_application_and_the_spa_exists_in_every_language(): void
    {
        foreach (glob(resource_path('js/lang/fr/*.json')) as $frenchFile) {
            $englishFile = resource_path('js/lang/en/'.basename($frenchFile));
            $this->assertFileExists($englishFile);
            $this->assertSame(
                array_keys(Arr::dot(json_decode(file_get_contents($frenchFile), true))),
                array_keys(Arr::dot(json_decode(file_get_contents($englishFile), true))),
                basename($frenchFile).' has different keys in French and in English.',
            );
        }

        foreach (glob(lang_path('fr/*.php')) as $frenchFile) {
            $englishFile = lang_path('en/'.basename($frenchFile));
            $this->assertFileExists($englishFile);
            $this->assertSame(
                array_keys(Arr::dot(require $frenchFile)),
                array_keys(Arr::dot(require $englishFile)),
                basename($frenchFile).' has different keys in French and in English.',
            );
        }
    }
}
