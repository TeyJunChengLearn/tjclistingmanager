<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_phone_number', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['phone_number_id']);
            $table->dropUnique(['owner_id', 'phone_number_id']);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('owners')->cascadeOnDelete();
            $table->foreign('phone_number_id')->references('id')->on('phone_numbers')->cascadeOnDelete();
            $table->unique(['user_id', 'owner_id', 'phone_number_id']);
        });
    }

    public function down(): void
    {
        Schema::table('owner_phone_number', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['phone_number_id']);
            $table->dropUnique(['user_id', 'owner_id', 'phone_number_id']);
            $table->dropColumn('user_id');
            $table->foreign('owner_id')->references('id')->on('owners')->cascadeOnDelete();
            $table->foreign('phone_number_id')->references('id')->on('phone_numbers')->cascadeOnDelete();
            $table->unique(['owner_id', 'phone_number_id']);
        });
    }
};
