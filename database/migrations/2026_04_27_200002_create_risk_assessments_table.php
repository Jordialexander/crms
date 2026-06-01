<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('impact_score')->default(1);       // 1-5
            $table->unsignedTinyInteger('complexity_score')->default(1);   // 1-5
            $table->unsignedTinyInteger('user_impact_score')->default(1);  // 1-5
            $table->unsignedTinyInteger('failure_probability_score')->default(1); // 1-5
            $table->unsignedTinyInteger('total_score')->default(4);
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->text('notes')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
