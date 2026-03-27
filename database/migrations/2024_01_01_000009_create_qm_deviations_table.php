<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_deviations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('qm_instance_id')->constrained('qm_instances')->onDelete('cascade');
            $table->foreignId('qm_instance_response_id')->nullable()->constrained('qm_instance_responses')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('low')->comment('low, medium, high, critical');
            $table->string('status')->default('open')->comment('open, acknowledged, in_progress, resolved, escalated, verified');
            $table->string('workflow_type')->default('simple')->comment('simple or full (HACCP)');

            // Simple workflow fields
            $table->text('corrective_action')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            // HACCP workflow fields
            $table->text('root_cause')->nullable();
            $table->text('preventive_action')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Escalation
            $table->integer('escalation_level')->default(0);
            $table->timestamp('escalated_at')->nullable();

            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['qm_instance_id', 'status'], 'qm_dev_instance_status_idx');
            $table->index(['qm_instance_id', 'severity'], 'qm_dev_instance_severity_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_deviations');
    }
};
