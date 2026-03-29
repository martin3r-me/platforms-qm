<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_wizard_fields', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('qm_template_id')->constrained('qm_templates')->onDelete('cascade');
            $table->string('technical_name', 100);
            $table->string('label', 255);
            $table->string('input_type', 50)->comment('text, number, date, currency, single_select, multi_select, boolean');
            $table->foreignId('qm_lookup_table_id')->nullable()->constrained('qm_lookup_tables')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index('uuid');
            $table->unique(['qm_template_id', 'technical_name'], 'qm_wf_template_techname_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_wizard_fields');
    }
};
