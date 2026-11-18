<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Secret token of the access request link of a private group or collection, generated on demand.
     */
    public function up(): void
    {
        foreach (['word_groups', 'group_collections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('share_token', 64)->nullable()->unique()->after('visibility');
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
                $table->dropUnique(['share_token']);
                $table->dropColumn('share_token');
            });
        }
    }
};
