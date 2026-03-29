<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qm_lookup_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('qm_lookup_table_id')->constrained('qm_lookup_tables')->onDelete('cascade');
            $table->string('label', 255);
            $table->string('value', 255);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index(['qm_lookup_table_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qm_lookup_entries');
    }
};
