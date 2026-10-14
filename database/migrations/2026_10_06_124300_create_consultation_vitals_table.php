<?php

declare(strict_types=1);

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
        Schema::create('consultation_vitals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->float('weight');
            $table->float('temperature');
            $table->integer('heart_rate');
            $table->integer('respiratory_rate');
            $table->integer('body_condition_score');
            $table->text('notes')->nullable();
            $table->foreignUuid('consultation_id');
            $table->foreign('consultation_id')->references('id')->on('consultations');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_vitals');
    }
};
