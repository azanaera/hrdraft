<?php

namespace App\Domain\TimeOff\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class TimeOffLedgerEntry extends Model
{
    protected $fillable = ['employment_id', 'policy_id', 'entry_type', 'hours', 'effective_date', 'related_request_id', 'notes'];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }

    public function policy()
    {
        return $this->belongsTo(TimeOffPolicy::class, 'policy_id');
    }
}
