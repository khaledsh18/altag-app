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
        Schema::table('supervisors', function (Blueprint $table) {
            $table->string('access_token', 32)->nullable()->unique();
            $table->boolean('is_data_completed')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('supervisors', function (Blueprint $table) {
            $table->dropColumn(['access_token', 'is_data_completed']);
        });
    }
};
