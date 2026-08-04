<?php

namespace App\Domain\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    protected $fillable = ['document_id', 'version_number', 'disk', 'file_path', 'file_size', 'mime_type', 'checksum', 'uploaded_by_user_id'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
