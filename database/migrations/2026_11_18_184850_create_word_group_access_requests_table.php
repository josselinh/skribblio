<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Requests to access a private item, one per item and requester.
     */
    public function up(): void
    {
        Schema::create('word_group_access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('word_group_id')->constrained('word_groups')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            // Set when the request was sent from a collection, without the requester knowing the group's name.
            $table->foreignUuid('via_group_collection_id')->nullable()->constrained('group_collections')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['word_group_id', 'user_id'], 'wg_access_requests_item_user_unique');
            $table->index(['user_id', 'status'], 'wg_access_requests_user_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_group_access_requests');
    }
};
