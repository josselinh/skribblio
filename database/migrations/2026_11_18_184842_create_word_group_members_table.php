<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users granted access to a private item through an accepted access request.
     */
    public function up(): void
    {
        Schema::create('word_group_members', function (Blueprint $table) {
            $table->foreignUuid('word_group_id')->constrained('word_groups')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['word_group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_group_members');
    }
};
