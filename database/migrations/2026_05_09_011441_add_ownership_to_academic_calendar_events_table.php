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
        Schema::table('academic_calendar_events', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->index(['created_by_id', 'created_by_type']);
        });
    }

    public function down(): void
    {
        Schema::table('academic_calendar_events', function (Blueprint $table) {
            $table->dropColumn(['created_by_id', 'created_by_type', 'is_shared', 'is_visible']);
        });
    }
};
