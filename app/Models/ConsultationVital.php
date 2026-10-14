<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ConsultationVitalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['weight', 'temperature', 'heart_rate', 'respiratory_rate', 'body_condition_score', 'notes', 'consultation_id'])]
class ConsultationVital extends Model
{
    /** @use HasFactory<ConsultationVitalFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'temperature' => 'float',
            'heart_rate' => 'integer',
            'respiratory_rate' => 'integer',
            'body_condition_score' => 'integer',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
