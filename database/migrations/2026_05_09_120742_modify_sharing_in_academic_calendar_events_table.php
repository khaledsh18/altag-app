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
            $table->dropColumn('is_shared');
            $table->json('shared_with')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('academic_calendar_events', function (Blueprint $table) {
            $table->dropColumn('shared_with');
            $table->boolean('is_shared')->default(false);
        });
    }
};
