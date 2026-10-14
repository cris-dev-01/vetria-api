<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Breed;
use App\Models\ClinicSetting;
use App\Models\ConsultationVital;
use App\Models\FollowUp;
use App\Models\PatientIdentifier;
use App\Models\Speciality;
use App\Models\Species;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use App\Models\Vaccination;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\TypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_idempotent_and_every_species_has_a_fallback_breed(): void
    {
        $this->seed(DatabaseSeeder::class);
        $breeds = Breed::count();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'test@example.com')->count());
        $this->assertSame(15, Species::count());
        $this->assertSame(23, Speciality::count());
        $this->assertSame($breeds, Breed::count());

        foreach (Species::all() as $species) {
            $this->assertTrue(
                $species->breeds()->whereIn('code', ['mixed', 'other'])->exists(),
                "La especie {$species->code} no tiene raza de respaldo.",
            );
        }
    }

    public function test_type_seeder_is_idempotent_and_creates_uuid_keys(): void
    {
        $this->seed(TypeSeeder::class);
        $this->seed(TypeSeeder::class);

        $this->assertSame(20, Type::count());
        $this->assertSame(5, Type::where('belong_to', 'patient_identifiers')->count());
        $this->assertSame(9, Type::where('belong_to', 'consultations')->count());
        $this->assertSame(6, Type::where('belong_to', 'follow_ups')->count());
        $this->assertTrue(Str::isUuid(Type::firstOrFail()->id));
    }

    public function test_status_seeder_creates_statuses_in_order(): void
    {
        $this->seed(StatusSeeder::class);
        $this->seed(StatusSeeder::class);

        $this->assertSame(8, Status::count());
        $this->assertSame(
            ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'],
            Status::where('belong_to', 'appointments')->orderBy('order')->pluck('code')->all(),
        );
        $this->assertSame(
            ['pending', 'completed', 'cancelled'],
            Status::where('belong_to', 'follow_ups')->orderBy('order')->pluck('code')->all(),
        );
    }

    public function test_every_factory_creates_a_valid_record_with_relations(): void
    {
        $this->assertTrue(Str::isUuid(ClinicSetting::factory()->create()->id));

        $identifier = PatientIdentifier::factory()->create();
        $this->assertSame('patient_identifiers', $identifier->type->belong_to);
        $this->assertCount(1, $identifier->patient->identifiers);
        $this->assertTrue($identifier->patient->breed->species->is($identifier->patient->species));
        $this->assertSame($identifier->patient->client->user->id, $identifier->patient->client->user->client->user_id);

        $appointment = Appointment::factory()->create();
        $this->assertSame('appointments', $appointment->status->belong_to);

        $vital = ConsultationVital::factory()->create();
        $this->assertTrue($vital->consultation->vitals->is($vital));
        $this->assertNull($vital->consultation->appointment_id);

        $this->assertCount(1, Vaccination::factory()->create()->consultation->vaccinations);

        $followUp = FollowUp::factory()->create();
        $this->assertSame('follow_ups', $followUp->status->belong_to);
        $this->assertNotNull($followUp->assignee);
    }
}
