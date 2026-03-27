<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_instances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('qm_template_id')->nullable()->constrained('qm_templates')->onDelete('set null')->comment('NULL = ad-hoc instance');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open')->comment('open, in_progress, completed, cancelled');
            $table->json('snapshot_data')->nullable()->comment('Frozen template structure at creation time');
            $table->string('public_token', 64)->nullable()->unique()->comment('Token for public guest access');
            $table->decimal('score', 5, 2)->nullable()->comment('Completion/conformity score 0-100');
            $table->foreignId('parent_instance_id')->nullable()->constrained('qm_instances')->onDelete('set null')->comment('Recurrence: parent instance');
            $table->json('recurrence_config')->nullable()->comment('Recurrence settings: frequency, next_due, etc.');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status'], 'qm_instances_team_status_idx');
            $table->index(['team_id', 'qm_template_id'], 'qm_instances_team_template_idx');
            $table->index('uuid');
            $table->index('public_token');
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_instances');
    }
};
