<?php

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
            $table->timestamp('reminder_sent_at')->nullable()->after('is_active');
            $table->timestamp('overdue_warning_sent_at')->nullable()->after('reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_schedules', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_at', 'overdue_warning_sent_at']);
        });
    }
};
