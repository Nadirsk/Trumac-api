<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retailer_visits', function (Blueprint $table) {
            $table->string('visit_type', 20)->default('planned')->after('pjp_id')->comment('planned or unplanned');
            $table->string('device_id')->nullable()->after('notes')->comment('Device identifier for fraud prevention');
            $table->decimal('check_in_accuracy', 8, 2)->nullable()->after('check_in_longitude')->comment('GPS accuracy in meters at check-in');
            $table->decimal('check_in_distance', 10, 2)->nullable()->after('check_in_accuracy')->comment('Distance from retailer in meters at check-in');
            $table->decimal('check_out_accuracy', 8, 2)->nullable()->after('check_out_longitude')->comment('GPS accuracy in meters at check-out');
            $table->decimal('check_out_distance', 10, 2)->nullable()->after('check_out_accuracy')->comment('Distance from retailer in meters at check-out');

            $table->index(['visit_type']);
        });
    }

    public function down(): void
    {
        Schema::table('retailer_visits', function (Blueprint $table) {
            $table->dropIndex(['visit_type']);
            $table->dropColumn(['visit_type', 'device_id', 'check_in_accuracy', 'check_in_distance', 'check_out_accuracy', 'check_out_distance']);
        });
    }
};
