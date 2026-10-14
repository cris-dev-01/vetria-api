<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PatientIdentifierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['value', 'type_id', 'patient_id'])]
class PatientIdentifier extends Model
{
    /** @use HasFactory<PatientIdentifierFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
