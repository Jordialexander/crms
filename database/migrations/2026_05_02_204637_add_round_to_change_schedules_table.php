<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('change_schedules', function (Blueprint $table) {
            $table->unsignedTinyInteger('round')->default(1)->after('change_request_id');
            $table->boolean('is_active')->default(true)->after('round');
        });
        // Set existing rows to round 1, active
        DB::table('change_schedules')->update(['round' => 1, 'is_active' => true]);
    }

    public function down(): void
    {
        Schema::table('change_schedules', function (Blueprint $table) {
            $table->dropColumn(['round', 'is_active']);
        });
    }
};
