<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_wizard_rule_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qm_wizard_rule_id')->constrained('qm_wizard_rules')->onDelete('cascade');
            $table->foreignId('qm_template_section_id')->constrained('qm_template_sections')->onDelete('cascade');
            $table->string('effect', 20)->default('show')->comment('show or hide');
            $table->timestamps();

            $table->unique(['qm_wizard_rule_id', 'qm_template_section_id'], 'qm_wrs_rule_section_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_wizard_rule_sections');
    }
};
