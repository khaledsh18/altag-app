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
        Schema::table('student_plans', function (Blueprint $table) {
            $table->boolean('is_approved')->default(true);
            $table->string('created_by_role')->default('teacher');
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_plans', function (Blueprint $table) {
            $table->dropColumn('is_approved');
            $table->dropColumn('created_by_role');
            $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
        });
    }
};
