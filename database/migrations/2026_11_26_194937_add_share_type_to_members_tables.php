<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the creator shares with each member (`App\Enums\ShareType`): partial (the member manages their own
     * contributions) or full (every contribution). Existing members keep the partial sharing they had.
     */
    public function up(): void
    {
        foreach (['word_group_members', 'group_collection_members'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('share_type', 16)->default('partial')->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['word_group_members', 'group_collection_members'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('share_type');
            });
        }
    }
};
