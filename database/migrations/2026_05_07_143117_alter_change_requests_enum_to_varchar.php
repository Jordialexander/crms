<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('change_type', 100)->default('normal')->change();
            $table->string('priority', 100)->default('medium')->change();
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->enum('change_type', ['standard', 'normal', 'emergency'])->default('normal')->change();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->change();
        });
    }
};
