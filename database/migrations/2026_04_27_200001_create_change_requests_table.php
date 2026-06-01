<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('cr_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->text('reason');
            $table->string('affected_service');
            $table->enum('change_type', ['standard', 'normal', 'emergency'])->default('normal');
            $table->string('category')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('medium');
            $table->text('impact')->nullable();
            $table->text('rollback_plan');
            $table->enum('status', [
                'draft',
                'need_review',
                'submitted',
                'under_review',
                'waiting_approval',
                'approved',
                'rejected',
                'canceled',
                'scheduled',
                'in_progress',
                'completed',
                'failed',
                'rollback',
                'closed',
            ])->default('draft');
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('approver_chain')->nullable();
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('current_approval_step')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason')->nullable();
            $table->text('rejection_note')->nullable();
            $table->text('cancellation_note')->nullable();
            $table->text('post_mortem_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
