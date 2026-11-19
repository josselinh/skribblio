<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the "open to contributions" option (existing groups stay open, as they were, and existing
     * collections stay closed), and records who added each group to a collection (its creator for the
     * existing ones).
     */
    public function up(): void
    {
        Schema::table('word_groups', function (Blueprint $table) {
            $table->boolean('accepts_contributions')->default(true)->after('visibility');
        });
        Schema::table('group_collections', function (Blueprint $table) {
            $table->boolean('accepts_contributions')->default(false)->after('visibility');
        });
        Schema::table('groups_collections', function (Blueprint $table) {
            $table->foreignUuid('added_by')->nullable()->after('word_group_id')->constrained('users')->nullOnDelete();
        });

        DB::table('groups_collections')->update([
            'added_by' => DB::raw('(select created_by from group_collections where group_collections.id = groups_collections.group_collection_id)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups_collections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('added_by');
        });
        foreach (['word_groups', 'group_collections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('accepts_contributions');
            });
        }
    }
};
