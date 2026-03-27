<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_section_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('qm_section_id')->constrained('qm_sections')->onDelete('cascade');
            $table->foreignId('qm_field_definition_id')->constrained('qm_field_definitions')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->boolean('is_required')->default(false);
            $table->string('behavior_rule')->default('always')->comment('always, conditional, random, time_based, risk_based');
            $table->json('behavior_config')->nullable()->comment('Rule-specific config');
            $table->timestamps();

            $table->unique(['qm_section_id', 'qm_field_definition_id'], 'qm_sf_section_field_uq');
            $table->index(['qm_section_id', 'position'], 'qm_sf_section_pos_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_section_fields');
    }
};
