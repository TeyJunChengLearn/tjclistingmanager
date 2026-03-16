<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ic', 30)->nullable()->comment('NRIC or Passport number');
            $table->string('mailing_address', 500)->nullable();
            $table->string('email', 150)->nullable();
            $table->enum('owner_type', ['individual', 'company', 'government'])->default('individual');
            $table->unsignedBigInteger('latest_activity_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
