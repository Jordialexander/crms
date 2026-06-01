<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('step')->default(1);
            $table->unsignedTinyInteger('resubmit_round')->default(1);
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'canceled'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['change_request_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
