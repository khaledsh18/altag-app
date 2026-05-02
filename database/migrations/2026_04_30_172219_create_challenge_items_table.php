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
        Schema::create('challenge_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // 'attendance', 'recitation_amount', 'recitation_quality'

            // For calculating progress
            $table->integer('current_progress')->default(0); // e.g. 5 days attended, or 10 pages read
            $table->integer('target_value')->default(0); // e.g. 10 days, 20 pages

            // All specific conditions (like plan_ids, max_unexcused_absences) go here
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenge_items');
    }
};
