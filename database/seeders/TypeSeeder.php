<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Seed the types table.
     */
    public function run(): void
    {
        $types = [
            'patient_identifiers' => [
                ['Microchip', 'microchip'],
                ['Arete', 'ear_tag'],
                ['Pasaporte', 'passport'],
                ['Registro', 'registration'],
                ['Tatuaje', 'tattoo'],
            ],
            'consultations' => [
                ['Consulta general', 'general'],
                ['Control', 'control'],
                ['Vacunación', 'vaccination'],
                ['Urgencia', 'emergency'],
                ['Procedimiento', 'procedure'],
                ['Cirugía', 'surgery'],
                ['Visita domiciliaria', 'home_visit'],
                ['Toma de muestras', 'sample_collection'],
                ['Otro', 'other'],
            ],
            'follow_ups' => [
                ['Llamada telefónica', 'phone_call'],
                ['Control', 'checkup'],
                ['Recordatorio vacunación', 'vaccination_reminder'],
                ['Resultado examen', 'test_result'],
                ['Agendar cita', 'appointment'],
                ['Otro', 'other'],
            ],
        ];

        foreach ($types as $belongTo => $items) {
            foreach ($items as [$name, $code]) {
                Type::updateOrCreate(
                    ['code' => $code, 'belong_to' => $belongTo],
                    ['name' => $name],
                );
            }
        }
    }
}
