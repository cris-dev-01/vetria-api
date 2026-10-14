<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'belong_to'])]
class Type extends Model
{
    /** @use HasFactory<TypeFactory> */
    use HasFactory, HasUuids, SoftDeletes;
}
