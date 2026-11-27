<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Makes the colourful style the default, existing accounts included: the style was introduced the same day,
     * so the sober accounts had almost all kept the former default.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('style')->default('colorful')->change();
        });

        DB::table('users')->where('style', 'sober')->update(['style' => 'colorful']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('style')->default('sober')->change();
        });
    }
};
