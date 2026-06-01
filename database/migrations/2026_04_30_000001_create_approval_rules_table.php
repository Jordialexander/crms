<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('change_type')->nullable(); // standard|normal|emergency or null=all
            $table->string('category')->nullable(); // free text or null=all
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->unsignedInteger('max_levels')->default(1); // how many manager levels required
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['enabled', 'priority']);
            $table->index(['enabled', 'priority', 'change_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_rules');
    }
};

