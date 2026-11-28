<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Makes English the default language of the interface and of the content, as most Skribbl.io players play in
     * English. Existing accounts and items keep their language.
     */
    public function up(): void
    {
        $this->setDefaultLanguage('en');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setDefaultLanguage('fr');
    }

    private function setDefaultLanguage(string $language): void
    {
        Schema::table('users', function (Blueprint $table) use ($language) {
            $table->string('locale', 2)->default($language)->change();
            $table->string('content_locale', 2)->default($language)->change();
        });
        foreach (['word_groups', 'group_collections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($language) {
                $table->string('language', 2)->default($language)->change();
            });
        }
    }
};
