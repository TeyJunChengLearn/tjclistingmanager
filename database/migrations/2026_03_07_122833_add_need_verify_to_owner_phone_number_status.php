<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `owner_phone_number` MODIFY `status` ENUM('need_verify','active','primary','inactive','disconnected','wrong_number') DEFAULT 'need_verify'");
    }

    public function down(): void
    {
        // Migrate any 'need_verify' rows to 'active' before removing the value from the enum
        DB::statement("UPDATE `owner_phone_number` SET `status` = 'active' WHERE `status` = 'need_verify'");
        DB::statement("ALTER TABLE `owner_phone_number` MODIFY `status` ENUM('active','primary','inactive','disconnected','wrong_number') DEFAULT 'active'");
    }
};
