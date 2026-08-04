<?php

namespace App\Domain\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'requires_signature', 'applicable_to'];

    protected function casts(): array
    {
        return ['requires_signature' => 'boolean'];
    }
}
