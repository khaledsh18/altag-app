<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_levels', function (Blueprint $table) {
            $table->foreignId('previous_level_id')->nullable()->after('id')->constrained('exam_levels')->nullOnDelete();
        });

        Schema::table('student_exams', function (Blueprint $table) {
            $table->string('status')->default('passed')->after('exam_level_id');
            // 'passed', 'failed', 'absent', 'cancelled'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_levels', function (Blueprint $table) {
            $table->dropForeign(['previous_level_id']);
            $table->dropColumn('previous_level_id');
        });

        Schema::table('student_exams', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
