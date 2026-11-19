<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the option restricting the contributions of a public item to its members (users the creator
     * accepted through the share link). Existing items keep their contributions open to everyone.
     */
    public function up(): void
    {
        foreach (['word_groups', 'group_collections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('contributions_members_only')->default(false)->after('accepts_contributions');
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
                $table->dropColumn('contributions_members_only');
            });
        }
    }
};
