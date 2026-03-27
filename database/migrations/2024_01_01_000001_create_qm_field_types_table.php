<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_field_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key')->comment('Unique type key, e.g. text, number, temperature');
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->comment('System types cannot be deleted');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade')->comment('NULL = system type, set = custom team type');
            $table->json('default_config')->nullable()->comment('Default field config for this type');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['key', 'team_id'], 'qm_field_types_key_team_uq');
            $table->index('uuid');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_field_types');
    }
};
