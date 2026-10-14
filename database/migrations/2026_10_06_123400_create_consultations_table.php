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
        Schema::create('consultations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('reason');
            $table->string('anamnesis');
            $table->text('clinical_exam');
            $table->text('assessment');
            $table->text('diagnosis');
            $table->string('treatment');
            $table->string('observations');
            $table->foreignUuid('patient_id');
            $table->foreignUuid('appointment_id')->nullable();
            $table->foreignUuid('type_id');
            $table->foreignUuid('speciality_id');
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('appointment_id')->references('id')->on('appointments');
            $table->foreign('type_id')->references('id')->on('types');
            $table->foreign('speciality_id')->references('id')->on('specialities');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
