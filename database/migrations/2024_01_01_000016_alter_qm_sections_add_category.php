<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qm_sections', function (Blueprint $table) {
            $table->string('category', 50)->default('standard')->after('description')->comment('standard or addon');
        });
    }

    public function down(): void
    {
        Schema::table('qm_sections', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
