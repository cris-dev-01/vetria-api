<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Breed;
use App\Models\Species;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BreedSeeder extends Seeder
{
    /**
     * Seed the breeds table. Requires SpeciesSeeder to have run first.
     *
     * Every species has a fallback breed ("Mestizo" and/or "Otra"), because
     * patients.breed_id is required.
     */
    public function run(): void
    {
        $breeds = [
            'dog' => [
                'Mestizo', 'Labrador Retriever', 'Golden Retriever', 'Pastor Alemán', 'Poodle',
                'Bulldog Francés', 'Bulldog Inglés', 'Beagle', 'Chihuahua', 'Yorkshire Terrier',
                'Schnauzer', 'Pug', 'Shih Tzu', 'Rottweiler', 'Boxer', 'Cocker Spaniel',
                'Husky Siberiano', 'Border Collie', 'Dachshund', 'Jack Russell Terrier', 'Maltés',
                'Pitbull', 'Dálmata', 'Doberman', 'Fox Terrier', 'Pastor Belga', 'Otra',
            ],
            'cat' => [
                'Mestizo', 'Siamés', 'Persa', 'Maine Coon', 'Angora', 'Bengalí', 'Ragdoll',
                'Británico de pelo corto', 'Sphynx', 'Azul Ruso', 'Otra',
            ],
            'rabbit' => ['Mestizo', 'Cabeza de León', 'Holandés Enano', 'Belier', 'Rex', 'Otra'],
            'ferret' => ['Doméstico', 'Otra'],
            'guinea_pig' => ['Americana', 'Abisinia', 'Peruana', 'Otra'],
            'hamster' => ['Sirio', 'Ruso', 'Roborovski', 'Otra'],
            'chinchilla' => ['Estándar', 'Otra'],
            'bird' => ['Canario', 'Periquito australiano', 'Cotorra', 'Agaporni', 'Cacatúa ninfa', 'Loro', 'Otra'],
            'reptile' => [
                'Tortuga de tierra', 'Tortuga de agua', 'Iguana', 'Gecko leopardo', 'Dragón barbudo',
                'Serpiente', 'Otra',
            ],
            'horse' => ['Caballo Chileno', 'Pura Sangre', 'Cuarto de Milla', 'Mestizo', 'Otra'],
            'cattle' => ['Holstein', 'Angus', 'Hereford', 'Mestizo', 'Otra'],
            'sheep' => ['Merino', 'Suffolk', 'Corriedale', 'Mestizo', 'Otra'],
            'goat' => ['Criolla', 'Saanen', 'Alpina', 'Otra'],
            'pig' => ['Landrace', 'Mini pig', 'Mestizo', 'Otra'],
            'other' => ['Sin raza definida'],
        ];

        foreach ($breeds as $speciesCode => $names) {
            $species = Species::where('code', $speciesCode)->firstOrFail();

            foreach ($names as $name) {
                Breed::updateOrCreate(
                    ['species_id' => $species->id, 'code' => $this->code($name)],
                    ['name' => $name],
                );
            }
        }
    }

    private function code(string $name): string
    {
        return match ($name) {
            'Mestizo' => 'mixed',
            'Otra', 'Sin raza definida' => 'other',
            default => Str::slug($name, '_'),
        };
    }
}
