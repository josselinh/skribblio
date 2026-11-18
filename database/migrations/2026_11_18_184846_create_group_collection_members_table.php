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
        Schema::create('group_collection_members', function (Blueprint $table) {
            $table->foreignUuid('group_collection_id')->constrained('group_collections')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['group_collection_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_collection_members');
    }
};
