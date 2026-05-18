<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            // NULL means created by a teacher for their single circle (existing behavior)
            // Non-null means created by a supervisor and applies to multiple circles
            $table->foreignId('supervisor_id')->nullable()->after('circle_id')
                ->constrained('supervisors')->nullOnDelete();
        });

        // Pivot: supervisor competitions can span multiple circles
        Schema::create('circle_leaderboard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leaderboard_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['circle_id', 'leaderboard_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');
        });

        Schema::dropIfExists('circle_leaderboard');
    }
};
