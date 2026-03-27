<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('qm_field_type_id')->constrained('qm_field_types')->onDelete('restrict');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('config')->nullable()->comment('Type-specific config: options, min/max, unit, etc.');
            $table->json('validation_rules')->nullable()->comment('Validation: required, min, max, regex, etc.');
            $table->json('i18n')->nullable()->comment('Translations: {de: {title: ..., description: ...}, en: {...}}');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'qm_field_type_id'], 'qm_fd_team_type_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_field_definitions');
    }
};
