<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->decimal('rental_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->boolean('is_rent_available')->default(false);
            $table->boolean('is_sale_available')->default(false);
            $table->json('status_filters')->nullable();
            $table->unsignedBigInteger('latest_activity_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_listings');
    }
};
