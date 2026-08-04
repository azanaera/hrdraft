<?php

namespace App\Domain\Documents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->whenLoaded('category', fn () => $this->category->name),
            'category_id' => $this->category_id,
            'requires_signature' => $this->whenLoaded('category', fn () => $this->category->requires_signature),
            'current_version' => $this->whenLoaded('currentVersion', fn () => $this->currentVersion ? [
                'id' => $this->currentVersion->id,
                'version_number' => $this->currentVersion->version_number,
                'mime_type' => $this->currentVersion->mime_type,
                'file_size' => $this->currentVersion->file_size,
                'uploaded_at' => $this->currentVersion->created_at?->toIso8601String(),
            ] : null),
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => $this->uploadedBy?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
