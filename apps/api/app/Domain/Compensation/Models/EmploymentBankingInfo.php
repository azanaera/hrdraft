<?php

namespace App\Domain\Compensation\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class EmploymentBankingInfo extends Model
{
    protected $table = 'employment_banking_info';

    protected $fillable = ['employment_id', 'provider', 'external_token', 'account_last_four', 'account_type', 'verified_at'];

    protected $hidden = ['external_token'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }
}
