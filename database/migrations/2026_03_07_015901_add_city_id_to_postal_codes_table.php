<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clear any orphaned rows (city_id may be 0 from a partial previous run)
        DB::table('local_areas')->whereNotNull('postal_code_id')->update(['postal_code_id' => null]);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('postal_codes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('postal_codes', function (Blueprint $table) {
            // Drop country_id if it still exists (partial migration guard)
            if (Schema::hasColumn('postal_codes', 'country_id')) {
                $table->dropForeign(['country_id']);
                $table->dropUnique(['country_id', 'code']);
                $table->dropColumn('country_id');
            }

            // Add city_id if not already added
            if (!Schema::hasColumn('postal_codes', 'city_id')) {
                $table->foreignId('city_id')->after('id')->constrained()->cascadeOnDelete();
            } else {
                $table->foreign('city_id')->references('id')->on('cities')->cascadeOnDelete();
            }

            $table->unique(['city_id', 'code']);
        });
    }

    public function down(): void
    {
        DB::table('local_areas')->whereNotNull('postal_code_id')->update(['postal_code_id' => null]);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('postal_codes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::table('postal_codes', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropUnique(['city_id', 'code']);
            $table->dropColumn('city_id');

            $table->foreignId('country_id')->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['country_id', 'code']);
        });
    }
};
