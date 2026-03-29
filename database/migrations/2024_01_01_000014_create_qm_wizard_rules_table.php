<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_wizard_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('qm_template_id')->constrained('qm_templates')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('rule_type', 50)->comment('field_value, multi_select_contains');
            $table->string('condition_field', 100)->comment('technical_name of wizard_field');
            $table->string('condition_operator', 20)->comment('=, !=, in, not_in, contains, exists');
            $table->json('condition_value')->comment('Value(s) for comparison');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('uuid');
            $table->index(['qm_template_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_wizard_rules');
    }
};
