<?php

namespace App\Domain\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    protected $fillable = ['documentable_type', 'documentable_id', 'category_id', 'title', 'uploaded_by_user_id', 'current_version_id'];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function currentVersion()
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function acknowledgments()
    {
        return $this->hasMany(DocumentAcknowledgment::class);
    }
}
