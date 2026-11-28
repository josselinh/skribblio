<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who renamed the word last: null until it is renamed, and again once that user deletes their account.
     */
    public function up(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->foreignUuid('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
