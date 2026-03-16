<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 30);
            $table->enum('type', ['mobile', 'home', 'work', 'fax'])->default('mobile');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_phone_numbers');
    }
};
