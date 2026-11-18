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
        Schema::create('group_collection_access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_collection_id')->constrained('group_collections')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['group_collection_id', 'user_id'], 'gc_access_requests_item_user_unique');
            $table->index(['user_id', 'status'], 'gc_access_requests_user_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_collection_access_requests');
    }
};
