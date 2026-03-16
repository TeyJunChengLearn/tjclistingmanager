<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_duplicate_decisions', function (Blueprint $table) {
            $table->id();
            // Always store the smaller id as owner_id_1 for consistent uniqueness
            $table->foreignId('owner_id_1')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('owner_id_2')->constrained('owners')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['owner_id_1', 'owner_id_2']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_duplicate_decisions');
    }
};
