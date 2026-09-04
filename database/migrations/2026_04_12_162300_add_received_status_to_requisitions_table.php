<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('draft','pending','approved','partial','fulfilled','received','cancelled') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE requisitions MODIFY COLUMN status ENUM('draft','pending','approved','partial','fulfilled','cancelled') DEFAULT 'draft'");
    }
};
