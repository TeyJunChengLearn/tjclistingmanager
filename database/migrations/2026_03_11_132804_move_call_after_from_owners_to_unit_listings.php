<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_listings', function (Blueprint $table) {
            $table->date('call_after')->nullable()->after('is_sale_available');
        });

        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('call_after');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->date('call_after')->nullable()->after('email');
        });

        Schema::table('unit_listings', function (Blueprint $table) {
            $table->dropColumn('call_after');
        });
    }
};
