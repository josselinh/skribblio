<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups_collections', function (Blueprint $table) {
            $table->foreignUuid('group_collection_id')->constrained('group_collections')->cascadeOnDelete();
            $table->foreignUuid('word_group_id')->constrained('word_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['group_collection_id', 'word_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups_collections');
    }
};
