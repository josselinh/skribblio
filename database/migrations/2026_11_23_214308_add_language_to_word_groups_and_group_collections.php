<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Language of the words of a group, and of the groups of a collection (all in the collection's language).
 * Existing items were all written in French.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['word_groups', 'group_collections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('language', 2)->default('fr')->after('name')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['word_groups', 'group_collections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['language']);
                $table->dropColumn('language');
            });
        }
    }
};
