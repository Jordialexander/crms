<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->onDelete('cascade');
            $table->dateTime('planned_start');
            $table->dateTime('planned_end');
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->integer('estimated_downtime_minutes')->default(0);
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_schedules');
    }
};
