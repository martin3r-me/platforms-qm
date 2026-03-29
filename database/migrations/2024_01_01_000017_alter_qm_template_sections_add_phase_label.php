<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qm_template_sections', function (Blueprint $table) {
            $table->string('phase_label', 255)->nullable()->after('is_required')->comment('Optional phase grouping label');
        });
    }

    public function down(): void
    {
        Schema::table('qm_template_sections', function (Blueprint $table) {
            $table->dropColumn('phase_label');
        });
    }
};
