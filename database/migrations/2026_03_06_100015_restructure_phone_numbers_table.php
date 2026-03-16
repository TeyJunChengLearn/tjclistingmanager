<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('owner_phone_numbers');

        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 30)->unique();
            $table->enum('type', ['mobile', 'home', 'work', 'fax'])->default('mobile');
            $table->timestamps();
        });

        Schema::create('owner_phone_number', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phone_number_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'active',
                'primary',
                'inactive',
                'disconnected',
                'wrong_number',
            ])->default('active');
            $table->timestamps();

            $table->unique(['owner_id', 'phone_number_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_phone_number');
        Schema::dropIfExists('phone_numbers');

        Schema::create('owner_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 30);
            $table->enum('type', ['mobile', 'home', 'work', 'fax'])->default('mobile');
            $table->timestamps();
        });
    }
};
