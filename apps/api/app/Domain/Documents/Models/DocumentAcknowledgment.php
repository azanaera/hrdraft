<?php

namespace App\Domain\Documents\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class DocumentAcknowledgment extends Model
{
    protected $fillable = [
        'document_id', 'employment_id', 'acknowledged_at', 'ip_address', 'signature_type', 'signature_data',
        'signature_provider', 'signature_envelope_id', 'signature_status',
    ];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }
}
