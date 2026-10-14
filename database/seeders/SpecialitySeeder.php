<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Speciality;
use Illuminate\Database\Seeder;

class SpecialitySeeder extends Seeder
{
    /**
     * Seed the specialities table.
     */
    public function run(): void
    {
        $specialities = [
            'Medicina general' => 'general_medicine',
            'Medicina preventiva' => 'preventive_medicine',
            'Cirugía' => 'surgery',
            'Medicina interna' => 'internal_medicine',
            'Dermatología' => 'dermatology',
            'Odontología' => 'dentistry',
            'Cardiología' => 'cardiology',
            'Oftalmología' => 'ophthalmology',
            'Traumatología y ortopedia' => 'orthopedics',
            'Oncología' => 'oncology',
            'Neurología' => 'neurology',
            'Gastroenterología' => 'gastroenterology',
            'Endocrinología' => 'endocrinology',
            'Nefrología y urología' => 'nephrology_urology',
            'Reproducción' => 'reproduction',
            'Imagenología' => 'imaging',
            'Anestesiología' => 'anesthesiology',
            'Urgencias y cuidados intensivos' => 'emergency_critical_care',
            'Etología' => 'ethology',
            'Nutrición' => 'nutrition',
            'Rehabilitación y fisioterapia' => 'rehabilitation',
            'Animales exóticos' => 'exotics',
            'Animales de producción' => 'livestock',
        ];

        $order = 1;

        foreach ($specialities as $name => $code) {
            Speciality::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'order' => $order++],
            );
        }
    }
}
