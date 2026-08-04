<?php

namespace App\Domain\ATS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'order', 'is_terminal'];

    protected function casts(): array
    {
        return ['is_terminal' => 'boolean'];
    }
}
