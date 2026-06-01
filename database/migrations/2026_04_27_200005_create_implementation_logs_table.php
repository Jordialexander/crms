<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('implementation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('implementer_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->enum('result_status', ['success', 'failed', 'rollback'])->default('success');
            $table->text('result_note');
            $table->text('issues')->nullable();
            $table->string('evidence_file')->nullable();
            $table->text('post_review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('implementation_logs');
    }
};
