<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type', 50);
            $table->string('outcome_code', 50)->nullable();
            $table->text('outcome_note')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
