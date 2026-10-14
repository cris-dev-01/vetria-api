<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    /**
     * Seed the species table.
     */
    public function run(): void
    {
        $species = [
            ['Perro', 'dog'],
            ['Gato', 'cat'],
            ['Conejo', 'rabbit'],
            ['Hurón', 'ferret'],
            ['Cobaya', 'guinea_pig'],
            ['Hámster', 'hamster'],
            ['Chinchilla', 'chinchilla'],
            ['Ave', 'bird'],
            ['Reptil', 'reptile'],
            ['Equino', 'horse'],
            ['Bovino', 'cattle'],
            ['Ovino', 'sheep'],
            ['Caprino', 'goat'],
            ['Porcino', 'pig'],
            ['Otra', 'other'],
        ];

        foreach ($species as [$name, $code]) {
            Species::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
