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
        Schema::create('exam_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('direction')->default('nas_to_baqarah'); // 'nas_to_baqarah' or 'baqarah_to_nas'
            $table->foreignId('start_ayah_id')->nullable()->constrained('ayahs')->nullOnDelete();
            $table->foreignId('end_ayah_id')->nullable()->constrained('ayahs')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_levels');
    }
};
