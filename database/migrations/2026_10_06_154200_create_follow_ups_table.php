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
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description');
            $table->date('due_date');
            $table->string('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('patient_id');
            $table->foreignUuid('consultation_id');
            $table->foreignUuid('assigned_to');
            $table->foreignUuid('type_id');
            $table->foreignUuid('status_id');
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('consultation_id')->references('id')->on('consultations');
            $table->foreign('assigned_to')->references('id')->on('users');
            $table->foreign('type_id')->references('id')->on('types');
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
