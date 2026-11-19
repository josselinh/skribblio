<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Words a user added to other people's groups outlive their account: their author becomes null
     * (anonymous) instead of deleting them. Words of the user's own groups still go with the groups.
     */
    public function up(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->uuid('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations. Anonymous words cannot get their author back and are deleted.
     */
    public function down(): void
    {
        DB::table('words')->whereNull('created_by')->delete();

        Schema::table('words', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->uuid('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
