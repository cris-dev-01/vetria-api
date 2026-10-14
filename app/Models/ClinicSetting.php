<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClinicSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'logo_path', 'primary_hex_color', 'phone_number', 'address'])]
class ClinicSetting extends Model
{
    /** @use HasFactory<ClinicSettingFactory> */
    use HasFactory, HasUuids, SoftDeletes;
}
