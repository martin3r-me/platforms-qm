<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_template_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('qm_template_id')->constrained('qm_templates')->onDelete('cascade');
            $table->foreignId('qm_section_id')->constrained('qm_sections')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->boolean('is_required')->default(true)->comment('Section must be completed');
            $table->timestamps();

            $table->unique(['qm_template_id', 'qm_section_id'], 'qm_ts_template_section_uq');
            $table->index(['qm_template_id', 'position'], 'qm_ts_template_pos_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_template_sections');
    }
};
