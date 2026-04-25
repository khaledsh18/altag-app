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
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('leaderboard_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leaderboard_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('points')->default(0);
            $table->timestamps();
        });

        Schema::create('leaderboard_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leaderboard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leaderboard_criterion_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();

            // A student can only get a specific criterion once per day
            $table->unique(['student_id', 'leaderboard_criterion_id', 'date'], 'student_criterion_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_scores');
        Schema::dropIfExists('leaderboard_criteria');
        Schema::dropIfExists('leaderboards');
    }
};
