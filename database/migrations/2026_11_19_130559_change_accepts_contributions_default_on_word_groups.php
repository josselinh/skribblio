<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New groups are closed to contributions by default, like collections. Existing groups keep their setting.
     */
    public function up(): void
    {
        Schema::table('word_groups', function (Blueprint $table) {
            $table->boolean('accepts_contributions')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('word_groups', function (Blueprint $table) {
            $table->boolean('accepts_contributions')->default(true)->change();
        });
    }
};
