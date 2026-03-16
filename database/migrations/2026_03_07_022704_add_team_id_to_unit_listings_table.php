<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_listings', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unit_listings', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
