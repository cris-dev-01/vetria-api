<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Seed the statuses table.
     */
    public function run(): void
    {
        $statuses = [
            'appointments' => [
                ['Agendada', 'scheduled', 1],
                ['Confirmada', 'confirmed', 2],
                ['Atendida', 'completed', 3],
                ['Cancelada', 'cancelled', 4],
                ['No asistió', 'no_show', 5],
            ],
            'follow_ups' => [
                ['Pendiente', 'pending', 1],
                ['Completado', 'completed', 2],
                ['Cancelado', 'cancelled', 3],
            ],
        ];

        foreach ($statuses as $belongTo => $items) {
            foreach ($items as [$name, $code, $order]) {
                Status::updateOrCreate(
                    ['code' => $code, 'belong_to' => $belongTo],
                    ['name' => $name, 'order' => $order],
                );
            }
        }
    }
}
