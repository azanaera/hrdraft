<?php

namespace App\Domain\Onboarding\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class BackgroundCheck extends Model
{
    protected $fillable = ['employment_id', 'check_type', 'provider', 'external_reference_id', 'status', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }
}
