<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_instance_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('qm_instance_id')->constrained('qm_instances')->onDelete('cascade');
            $table->foreignId('qm_field_definition_id')->constrained('qm_field_definitions')->onDelete('restrict');
            $table->foreignId('qm_section_id')->nullable()->constrained('qm_sections')->onDelete('set null')->comment('Which section this response belongs to');
            $table->json('value')->nullable()->comment('The actual response value (JSON for flexibility)');
            $table->boolean('is_deviation')->default(false)->comment('Quick flag: was this a deviation?');
            $table->text('notes')->nullable();
            $table->foreignId('responded_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['qm_instance_id', 'qm_section_id'], 'qm_ir_instance_section_idx');
            $table->index(['qm_instance_id', 'qm_field_definition_id'], 'qm_ir_instance_field_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_instance_responses');
    }
};
